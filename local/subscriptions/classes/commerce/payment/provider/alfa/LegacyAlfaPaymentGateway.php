<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\constants\Operation;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\CheckoutInitResult;

/**
 * Transitional bridge between Commerce and the existing Alfa gateway.
 *
 * The existing AlfaGateway remains responsible for:
 * - register.do;
 * - provider authentication;
 * - amount conversion and validation;
 * - order number generation;
 * - persistence of attempts and order identifiers;
 * - payment form URL persistence.
 *
 * This bridge only validates and translates Commerce data.
 */
final class LegacyAlfaPaymentGateway
    implements AlfaPaymentGateway {

    private PaymentGatewayInterface $legacygateway;

    public function __construct(
        private readonly LegacyPaymentRequestAdapter $requestadapter,
        ?PaymentGatewayInterface $legacygateway = null
    ) {
        $this->legacygateway = $legacygateway
            ?? PaymentGatewayFactory::for(
                Provider::ALFA
            );
    }

    public function is_configured(): bool {
        $environment = Provider::env(
            Provider::ALFA
        );

        $environment = $environment === 'live'
            ? 'live'
            : 'test';

        $base = trim(
            (string)get_config(
                'local_subscriptions',
                'alfa_' . $environment . '_api_base'
            )
        );

        $token = trim(
            (string)get_config(
                'local_subscriptions',
                'alfa_' . $environment . '_token'
            )
        );

        $username = trim(
            (string)get_config(
                'local_subscriptions',
                'alfa_' . $environment . '_username'
            )
        );

        $password = trim(
            (string)get_config(
                'local_subscriptions',
                'alfa_' . $environment . '_password'
            )
        );

        $hascredentials = $token !== ''
            || (
                $username !== ''
                && $password !== ''
            );

        return $base !== ''
            && $hascredentials;
    }

    public function register(
        AlfaGatewayRequest $request
    ): AlfaGatewayResponse {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $request->get_metadata(),
                Provider::ALFA
            );

        $legacyrequest =
            $this->requestadapter->load_and_validate(
                $context,
                $request->get_amount_minor(),
                $request->get_currency(),
                $request->get_customer_email(),
                $this->resolve_user_id(
                    $request->get_metadata()
                )
            );

        $options = $this->build_legacy_options(
            $request,
            $context,
            $legacyrequest
        );

        try {
            $result =
                $this->legacygateway
                    ->create_checkout_session(
                        $legacyrequest,
                        $options
                    );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'The Legacy Alfa gateway failed to register the payment.',
                Provider::ALFA,
                'legacy_alfa_registration_failed',
                [
                    'commerce_reference' =>
                        $this->resolve_commerce_reference(
                            $request
                        ),

                    'commerce_payment_id' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_payment_id'
                        ),

                    'commerce_purchase_uuid' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_purchase_uuid'
                        ),
                ],
                $exception
            );
        }

        return $this->map_checkout_result(
            $request,
            $result
        );
    }

    public function retrieve(
        string $orderid
    ): AlfaGatewayResponse {
        throw new CommercePaymentProviderException(
            'Alfa payment retrieval is not exposed by the Legacy gateway.',
            Provider::ALFA,
            'legacy_alfa_retrieval_not_supported',
            [
                'providerpaymentid' =>
                    trim($orderid),
            ]
        );
    }

    public function cancel(
        string $orderid
    ): AlfaGatewayResponse {
        throw new CommercePaymentProviderException(
            'Alfa payment cancellation is not exposed by the Legacy gateway.',
            Provider::ALFA,
            'legacy_alfa_cancellation_not_supported',
            [
                'providerpaymentid' =>
                    trim($orderid),
            ]
        );
    }

    private function build_legacy_options(
        AlfaGatewayRequest $request,
        LegacyPaymentRequestContext $context,
        \stdClass $legacyrequest
    ): array {
        $metadata = $request->get_metadata();

        $options = [
            /*
             * Exact option names consumed by the existing AlfaGateway.
             */
            'returnurl' =>
                $request->get_return_url(),

            'failurl' =>
                $request->get_fail_url(),

            'language' =>
                $this->resolve_language(
                    $context,
                    $legacyrequest
                ),

            'email' =>
                $legacyrequest->email
                    ?? $request->get_customer_email(),

            'firstname' =>
                $legacyrequest->firstname
                    ?? null,

            'lastname' =>
                $legacyrequest->lastname
                    ?? null,

            'payment_request_table' =>
                $context->get_payment_request_table(),

            'order_number_prefix' =>
                $context->get_order_number_prefix(),

            /*
             * The current Legacy AlfaGateway uses this option when checking
             * the locked price against the catalogue price.
             */
            'operation' =>
                $this->resolve_operation(
                    $metadata,
                    $legacyrequest
                ),

            /*
             * The Legacy gateway does not consume these values yet, but they
             * remain available for diagnostics and future idempotency work.
             */
            'amount_minor' =>
                $request->get_amount_minor(),

            'idempotency_key' =>
                $request->get_idempotency_key(),

            'commerce_reference' =>
                $this->resolve_commerce_reference(
                    $request
                ),

            'payment_context' =>
                $context->get_payment_context(),
        ];

        return array_filter(
            $options,
            static fn(mixed $value): bool =>
                $value !== null
                && $value !== ''
        );
    }

    private function resolve_language(
        LegacyPaymentRequestContext $context,
        \stdClass $legacyrequest
    ): string {
        $language =
            $context->get_language();

        if (
            $language === null
            && isset($legacyrequest->buyer_lang)
        ) {
            $language = trim(
                (string)$legacyrequest->buyer_lang
            );
        }

        if ($language === null) {
            $language = current_language();
        }

        return strtolower($language) === 'ru'
            ? 'ru'
            : 'en';
    }

    private function resolve_operation(
        array $metadata,
        \stdClass $legacyrequest
    ): string {
        $operation = $metadata[
            'legacy_operation'
        ] ?? (
            $legacyrequest->operation
            ?? Operation::PURCHASE_NEW
        );

        if (!is_scalar($operation)) {
            throw new CommercePaymentProviderException(
                'The Commerce Alfa metadata contains an invalid operation.',
                Provider::ALFA,
                'legacy_alfa_operation_invalid'
            );
        }

        $operation = trim(
            (string)$operation
        );

        if ($operation === '') {
            return Operation::PURCHASE_NEW;
        }

        return $operation;
    }

    private function map_checkout_result(
        AlfaGatewayRequest $request,
        CheckoutInitResult $result
    ): AlfaGatewayResponse {
        $redirecturl = trim(
            $result->redirect_url
        );

        $orderid = trim(
            (string)(
                $result->provider_session_id
                ?? ''
            )
        );

        if ($redirecturl === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Alfa gateway returned no payment form URL.',
                Provider::ALFA,
                'legacy_alfa_form_url_missing',
                [
                    'commerce_reference' =>
                        $this->resolve_commerce_reference(
                            $request
                        ),

                    'commerce_payment_id' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_payment_id'
                        ),

                    'commerce_purchase_uuid' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_purchase_uuid'
                        ),
                ]
            );
        }

        if ($orderid === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Alfa gateway returned no order identifier.',
                Provider::ALFA,
                'legacy_alfa_order_id_missing',
                [
                    'commerce_reference' =>
                        $this->resolve_commerce_reference(
                            $request
                        ),

                    'commerce_payment_id' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_payment_id'
                        ),

                    'commerce_purchase_uuid' =>
                        $this->required_native_identity(
                            $request->get_metadata(),
                            'commerce_purchase_uuid'
                        ),
                ]
            );
        }

        return new AlfaGatewayResponse(
            $orderid,
            AlfaGatewayResponse::STATUS_REGISTERED,
            $redirecturl,
            null,
            null,
            [
                'commerce_reference' =>
                    $this->resolve_commerce_reference(
                        $request
                    ),

                'commerce_payment_id' =>
                    $this->required_native_identity(
                        $request->get_metadata(),
                        'commerce_payment_id'
                    ),

                'commerce_purchase_uuid' =>
                    $this->required_native_identity(
                        $request->get_metadata(),
                        'commerce_purchase_uuid'
                    ),

                'alfa_order_id' =>
                    $orderid,
            ]
        );
    }

    private function required_native_identity(
        array $metadata,
        string $key
    ): string {
        $value = $metadata[$key] ?? null;

        if (!is_scalar($value) || trim((string)$value) === '') {
            throw new CommercePaymentProviderException(
                'The Commerce Alfa request is missing its Native payment identity.',
                Provider::ALFA,
                'commerce_alfa_native_identity_missing',
                [
                    'missingkey' => $key,
                    'commerce_reference' => $metadata['commerce_reference'] ?? null,
                ]
            );
        }

        return trim((string)$value);
    }

    private function resolve_commerce_reference(
        AlfaGatewayRequest $request
    ): string {
        $reference = $request->get_metadata()[
            'commerce_reference'
        ] ?? null;

        if (
            is_scalar($reference)
            && trim((string)$reference) !== ''
        ) {
            return trim(
                (string)$reference
            );
        }

        /*
         * AlfaGatewayRequest still carries an order number generated by the
         * Commerce provider. The actual Alfa order number remains generated
         * by the Legacy gateway as "<prefix>-<request id>-<attempt>".
         */
        return $request->get_order_number();
    }

    private function resolve_user_id(
        array $metadata
    ): ?int {
        $value = $metadata['userid']
            ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (
            is_int($value)
            || (
                is_string($value)
                && ctype_digit($value)
            )
        ) {
            $value = (int)$value;

            return $value > 0
                ? $value
                : null;
        }

        throw new CommercePaymentProviderException(
            'The Commerce Alfa metadata contains an invalid user identifier.',
            Provider::ALFA,
            'legacy_alfa_user_id_invalid',
            [
                'userid' =>
                    $value,
            ]
        );
    }
}