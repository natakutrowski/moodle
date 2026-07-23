<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\CheckoutInitResult;

/**
 * Transitional bridge between Commerce and the existing Stripe gateway.
 *
 * This class must remain thin. Stripe API calls, SDK configuration and
 * provider-specific payload construction remain owned by the Legacy gateway.
 *
 * To be removed after the payment infrastructure has been fully migrated
 * under the Commerce namespace.
 */
final class LegacyStripePaymentGateway
    implements StripePaymentGateway {

    private PaymentGatewayInterface $legacygateway;

    public function __construct(
        private readonly LegacyPaymentRequestAdapter $requestadapter,
        ?PaymentGatewayInterface $legacygateway = null
    ) {
        $this->legacygateway = $legacygateway
            ?? PaymentGatewayFactory::for(
                Provider::STRIPE
            );
    }

    public function is_configured(): bool {
        $environment = Provider::env(
            Provider::STRIPE
        );

        $environment = $environment === 'live'
            ? 'live'
            : 'test';

        $secret = get_config(
            'local_subscriptions',
            'stripe_' . $environment . '_secret'
        );

        return trim((string)$secret) !== '';
    }

    public function create_checkout_session(
        StripeGatewayRequest $request
    ): StripeGatewayResponse {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $request->get_metadata(),
                Provider::STRIPE
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
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway failed to create a checkout session.',
                Provider::STRIPE,
                'legacy_stripe_checkout_creation_failed',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),

                    'legacy_payment_request_table' =>
                        $context->get_payment_request_table(),
                ],
                $exception
            );
        }

        return $this->map_checkout_result(
            $request,
            $context,
            $result
        );
    }

    public function retrieve(
        string $paymentid
    ): StripeGatewayResponse {
        throw new CommercePaymentProviderException(
            'Stripe payment retrieval is not exposed by the Legacy gateway.',
            Provider::STRIPE,
            'legacy_stripe_retrieval_not_supported',
            [
                'providerpaymentid' =>
                    trim($paymentid),
            ]
        );
    }

    public function cancel(
        string $paymentid
    ): StripeGatewayResponse {
        throw new CommercePaymentProviderException(
            'Stripe payment cancellation is not exposed by the Legacy gateway.',
            Provider::STRIPE,
            'legacy_stripe_cancellation_not_supported',
            [
                'providerpaymentid' =>
                    trim($paymentid),
            ]
        );
    }

    private function build_legacy_options(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context,
        \stdClass $legacyrequest
    ): array {
        $metadata = $request->get_metadata();

        $mode = $context->get_mode();

        if (
            !in_array(
                $mode,
                [
                    'payment',
                    'subscription',
                ],
                true
            )
        ) {
            throw new CommercePaymentProviderException(
                'The requested Legacy Stripe checkout mode is invalid.',
                Provider::STRIPE,
                'legacy_stripe_mode_invalid',
                [
                    'mode' =>
                        $mode,

                    'commerce_reference' =>
                        $request->get_reference(),
                ]
            );
        }

        $options = [
            'mode' =>
                $mode,

            /*
             * Commerce always uses the amount already locked and validated
             * against the persisted Legacy Payment Request.
             */
            'use_locked_amount' =>
                true,

            'amount_minor' =>
                $request->get_amount_minor(),

            'product_name' =>
                $this->build_product_name(
                    $request
                ),

            'success_url' =>
                $request->get_success_url(),

            'cancel_url' =>
                $request->get_cancel_url(),

            'email' =>
                $legacyrequest->email
                    ?? $request->get_customer_email(),

            'firstname' =>
                $legacyrequest->firstname
                    ?? null,

            'lastname' =>
                $legacyrequest->lastname
                    ?? null,

            'metadata' =>
                $this->build_stripe_metadata(
                    $request,
                    $context
                ),

            /*
             * Kept for the later Stripe idempotency hardening.
             * The current Legacy StripeGateway does not consume it yet.
             */
            'idempotency_key' =>
                $request->get_idempotency_key(),
        ];

        if ($mode === 'subscription') {
            $stripepriceid =
                $context->get_stripe_price_id();

            if ($stripepriceid !== null) {
                $options['price_map'] = [
                    'stripe_price_id' =>
                        $stripepriceid,
                ];
            }
        }

        if (
            isset($metadata['legacy_operation'])
            && is_scalar(
                $metadata['legacy_operation']
            )
        ) {
            $options['operation'] = trim(
                (string)$metadata[
                    'legacy_operation'
                ]
            );
        }

        if (
            isset($metadata['legacy_ref_subscription_id'])
            && (
                is_int(
                    $metadata[
                        'legacy_ref_subscription_id'
                    ]
                )
                || (
                    is_string(
                        $metadata[
                            'legacy_ref_subscription_id'
                        ]
                    )
                    && ctype_digit(
                        $metadata[
                            'legacy_ref_subscription_id'
                        ]
                    )
                )
            )
        ) {
            $options['ref_sub_id'] = (int)$metadata[
                'legacy_ref_subscription_id'
            ];
        }

        return $options;
    }

    private function build_stripe_metadata(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context
    ): array {
        $metadata = [
            'commerce_reference' =>
                $request->get_reference(),

            'payment_context' =>
                $context->get_payment_context(),

            'legacy_payment_request_id' =>
                (string)$context
                    ->get_payment_request_id(),

            'legacy_payment_request_table' =>
                $context
                    ->get_payment_request_table(),
        ];

        $source = $request->get_metadata();

        foreach (
            [
                'legacy_plan_id' =>
                    'planid',

                'legacy_product_id' =>
                    'productid',

                'legacy_operation' =>
                    'operation',

                'legacy_language' =>
                    'uilang',

                'legacy_slug' =>
                    'slug',

                'userid' =>
                    'userid',
            ] as $sourcekey => $targetkey
        ) {
            if (
                !array_key_exists(
                    $sourcekey,
                    $source
                )
            ) {
                continue;
            }

            $value = $source[$sourcekey];

            if (
                !is_scalar($value)
                || trim((string)$value) === ''
            ) {
                continue;
            }

            $metadata[$targetkey] =
                (string)$value;
        }

        return $metadata;
    }

    private function build_product_name(
        StripeGatewayRequest $request
    ): string {
        $descriptions = [];

        foreach ($request->get_lines() as $line) {
            if (!is_array($line)) {
                continue;
            }

            $description = trim(
                (string)(
                    $line['description']
                    ?? ''
                )
            );

            if ($description !== '') {
                $descriptions[] =
                    $description;
            }
        }

        $descriptions = array_values(
            array_unique(
                $descriptions
            )
        );

        if ($descriptions === []) {
            return get_string(
                'stripe:productname',
                'local_subscriptions',
                'CampusFR'
            );
        }

        return \core_text::substr(
            implode(
                ' + ',
                $descriptions
            ),
            0,
            255
        );
    }

    private function map_checkout_result(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context,
        CheckoutInitResult $result
    ): StripeGatewayResponse {
        $redirecturl = trim(
            $result->redirect_url
        );

        $sessionid = trim(
            (string)(
                $result->provider_session_id
                ?? ''
            )
        );

        if ($redirecturl === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway returned no checkout URL.',
                Provider::STRIPE,
                'legacy_stripe_checkout_url_missing',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),
                ]
            );
        }

        if ($sessionid === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway returned no session identifier.',
                Provider::STRIPE,
                'legacy_stripe_session_id_missing',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),
                ]
            );
        }

        return new StripeGatewayResponse(
            $sessionid,
            StripeGatewayResponse::STATUS_OPEN,
            $redirecturl,
            null,
            null,
            [
                'commerce_reference' =>
                    $request->get_reference(),

                'legacy_payment_request_id' =>
                    $context->get_payment_request_id(),

                'legacy_payment_request_table' =>
                    $context
                        ->get_payment_request_table(),

                'legacy_payment_context' =>
                    $context
                        ->get_payment_context(),

                'stripe_session_id' =>
                    $sessionid,
            ]
        );
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
            'The Commerce Stripe metadata contains an invalid user identifier.',
            Provider::STRIPE,
            'legacy_stripe_user_id_invalid',
            [
                'userid' =>
                    $value,
            ]
        );
    }
}