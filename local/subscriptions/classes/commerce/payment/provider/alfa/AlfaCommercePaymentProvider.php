<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;

/**
 * Commerce adapter for the existing Alfa payment integration.
 */
final class AlfaCommercePaymentProvider
    implements CommercePaymentProvider {

    public const KEY = 'alfa';

    public function __construct(
        private readonly AlfaPaymentGateway $gateway,
        private readonly AlfaPaymentProviderConfiguration $configuration
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function get_priority(): int {
        return $this->configuration->get_priority();
    }

    public function is_available(): bool {
        return $this->configuration->is_enabled()
            && $this->gateway->is_configured();
    }

    public function get_capabilities():
        CommercePaymentProviderCapabilities {
        return new CommercePaymentProviderCapabilities(
            $this->configuration->get_currencies(),

            // Alfa redirects the customer to its hosted payment form.
            true,

            // Payment cancellation is not exposed by the Legacy gateway.
            false,

            // Status lookup exists internally but is not exposed through
            // PaymentGatewayInterface.
            false,

            // No Commerce refund operation is implemented.
            false,

            // Several Commerce lines can be aggregated into one Alfa payment.
            true,

            [
                'checkout_mode' =>
                    'bank_form',

                'provider' =>
                    self::KEY,

                'confirmation_mode' =>
                    'legacy_return_or_webhook',

                'bridge' =>
                    'legacy',
            ]
        );
    }

    public function supports(
        CommercePaymentRequest $request
    ): bool {
        if (!$request->requires_payment()) {
            return false;
        }

        return $this
            ->get_capabilities()
            ->supports_currency(
                $request->get_currency()
            );
    }

    public function validate(
        CommercePaymentRequest $request
    ): CommercePaymentProviderValidationResult {
        $result =
            CommercePaymentProviderValidationResult::valid();

        if (!$this->configuration->is_enabled()) {
            $result->add_error(
                'alfa_disabled',
                'The Alfa Commerce provider is disabled.'
            );
        }

        if (!$this->gateway->is_configured()) {
            $result->add_error(
                'alfa_not_configured',
                'The Alfa gateway is not configured.'
            );
        }

        if (!$request->requires_payment()) {
            $result->add_error(
                'alfa_free_payment_not_supported',
                'Alfa must not initialize zero-amount payments.'
            );
        }

        if (
            !$this
                ->get_capabilities()
                ->supports_currency(
                    $request->get_currency()
                )
        ) {
            $result->add_error(
                'alfa_currency_not_supported',
                'Alfa does not support the requested currency.',
                [
                    'currency' =>
                        $request->get_currency(),
                ]
            );
        }

        if ($request->get_return_url() === null) {
            $result->add_error(
                'alfa_return_url_missing',
                'Alfa requires a payment return URL.'
            );
        }

        if ($request->get_cancel_url() === null) {
            $result->add_error(
                'alfa_fail_url_missing',
                'Alfa requires a payment failure URL.'
            );
        }

        return $result;
    }

    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $validation = $this->validate($request);

        if (!$validation->is_valid()) {
            throw new CommercePaymentProviderException(
                'The Alfa payment request is invalid.',
                self::KEY,
                'alfa_validation_failed',
                [
                    'validation' =>
                        $validation->to_array(),
                ]
            );
        }

        try {
            $response = $this->gateway->register(
                $this->build_gateway_request(
                    $request,
                    $context
                )
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment registration failed.',
                self::KEY,
                'alfa_initialization_failed',
                [
                    'requestreference' =>
                        $request->get_reference(),
                ],
                $exception
            );
        }

        return $this->map_response(
            $request->get_reference(),
            $response
        );
    }

    public function retrieve(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $providerpaymentid = trim($providerpaymentid);

        if ($providerpaymentid === '') {
            throw new CommercePaymentProviderException(
                'The Alfa order identifier cannot be empty.',
                self::KEY,
                'alfa_order_id_missing'
            );
        }

        try {
            $response = $this->gateway->retrieve(
                $providerpaymentid
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment retrieval failed.',
                self::KEY,
                'alfa_retrieval_failed',
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ],
                $exception
            );
        }

        return $this->map_response(
            $this->resolve_request_reference(
                $response,
                $providerpaymentid
            ),
            $response
        );
    }

    public function cancel(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $providerpaymentid = trim($providerpaymentid);

        if ($providerpaymentid === '') {
            throw new CommercePaymentProviderException(
                'The Alfa order identifier cannot be empty.',
                self::KEY,
                'alfa_order_id_missing'
            );
        }

        try {
            $response = $this->gateway->cancel(
                $providerpaymentid
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment cancellation failed.',
                self::KEY,
                'alfa_cancellation_failed',
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ],
                $exception
            );
        }

        return $this->map_response(
            $this->resolve_request_reference(
                $response,
                $providerpaymentid
            ),
            $response
        );
    }

    private function build_gateway_request(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): AlfaGatewayRequest {
        $metadata = array_merge(
            $request->get_metadata(),
            [
                /*
                 * The following values are authoritative and cannot be
                 * overridden by arbitrary request metadata.
                 */
                'commerce_reference' =>
                    $request->get_reference(),

                'userid' =>
                    $request
                        ->get_customer()
                        ->get_user_id(),

                'customer_email' =>
                    $request
                        ->get_customer()
                        ->get_email(),

                'environment' =>
                    $context->is_live()
                        ? 'live'
                        : 'test',
            ]
        );

        $metadata = array_filter(
            $metadata,
            static fn(mixed $value): bool =>
                $value !== null
                && $value !== ''
        );

        return new AlfaGatewayRequest(
            $this->build_order_number(
                $request->get_reference()
            ),
            $request->get_amount_minor(),
            $request->get_currency(),
            $this->build_description($request),
            $request
                ->get_customer()
                ->get_email(),
            (string)$request->get_return_url(),
            (string)$request->get_cancel_url(),
            $context->get_idempotency_key(),
            $metadata
        );
    }

    private function build_order_number(
        string $reference
    ): string {
        $normalised = preg_replace(
            '/[^a-zA-Z0-9_-]+/',
            '-',
            $reference
        );

        $normalised = trim(
            (string)$normalised,
            '-_'
        );

        if ($normalised === '') {
            $normalised = 'commerce';
        }

        return substr(
            $normalised,
            0,
            80
        );
    }

    private function build_description(
        CommercePaymentRequest $request
    ): string {
        $descriptions = array_map(
            static fn($line): string =>
                $line->get_description(),
            $request->get_lines()
        );

        $description = implode(
            ' + ',
            $descriptions
        );

        return \core_text::substr(
            $description,
            0,
            255
        );
    }

    private function map_response(
        string $requestreference,
        AlfaGatewayResponse $response
    ): CommercePaymentResult {
        $status = $this->map_status(
            $response->get_status()
        );

        $metadata = array_merge(
            $response->get_metadata(),
            [
                'alfa_status' =>
                    $response->get_status(),
            ]
        );

        if (
            $status ===
                CommercePaymentStatus::REQUIRES_ACTION
        ) {
            $formurl =
                $response->get_form_url();

            if ($formurl === null) {
                throw new CommercePaymentProviderException(
                    'Alfa returned a registered order without a payment form URL.',
                    self::KEY,
                    'alfa_form_url_missing'
                );
            }

            return CommercePaymentResult::requires_action(
                $requestreference,
                self::KEY,
                $response->get_order_id(),
                CommercePaymentAction::redirect(
                    $formurl
                ),
                $metadata
            );
        }

        if ($status === CommercePaymentStatus::FAILED) {
            return CommercePaymentResult::failed(
                $requestreference,
                self::KEY,
                $response->get_error_code()
                    ?? 'alfa_payment_failed',
                $response->get_error_message()
                    ?? 'Alfa reported a payment failure.',
                $response->get_order_id(),
                $metadata
            );
        }

        return new CommercePaymentResult(
            $requestreference,
            self::KEY,
            $status,
            $response->get_order_id(),
            null,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    private function map_status(
        string $status
    ): string {
        return match (
            strtolower(trim($status))
        ) {
            AlfaGatewayResponse::STATUS_REGISTERED =>
                CommercePaymentStatus::REQUIRES_ACTION,

            AlfaGatewayResponse::STATUS_PREAUTHORISED,
            AlfaGatewayResponse::STATUS_PENDING =>
                CommercePaymentStatus::PENDING,

            AlfaGatewayResponse::STATUS_PAID =>
                CommercePaymentStatus::SUCCEEDED,

            AlfaGatewayResponse::STATUS_DECLINED =>
                CommercePaymentStatus::FAILED,

            AlfaGatewayResponse::STATUS_CANCELLED =>
                CommercePaymentStatus::CANCELLED,

            AlfaGatewayResponse::STATUS_EXPIRED =>
                CommercePaymentStatus::EXPIRED,

            AlfaGatewayResponse::STATUS_REFUNDED =>
                CommercePaymentStatus::REFUNDED,

            AlfaGatewayResponse::STATUS_PARTIALLY_REFUNDED =>
                CommercePaymentStatus::PARTIALLY_REFUNDED,

            default =>
                CommercePaymentStatus::PENDING,
        };
    }

    private function resolve_request_reference(
        AlfaGatewayResponse $response,
        string $fallback
    ): string {
        $reference =
            $response->get_metadata()[
                'commerce_reference'
            ] ?? null;

        if (
            is_string($reference)
            && trim($reference) !== ''
        ) {
            return trim($reference);
        }

        return 'alfa-payment:' . $fallback;
    }
}