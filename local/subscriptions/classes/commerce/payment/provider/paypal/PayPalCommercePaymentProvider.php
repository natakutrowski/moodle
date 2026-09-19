<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRequest;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;

defined('MOODLE_INTERNAL') || die();

final class PayPalCommercePaymentProvider
    implements CommercePaymentProvider, CommerceRefundCapablePaymentProvider, CommerceRefundHistoryCapablePaymentProvider {

    public const KEY = 'paypal';

    public function __construct(
        private readonly PayPalPaymentGateway $gateway,
        private readonly PayPalPaymentProviderConfiguration $configuration
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
            true,
            false,
            true,
            true,
            true,
            [
                'checkout_mode' => 'paypal_checkout',
                'provider' => self::KEY,
                'confirmation_mode' =>
                    'return_and_webhook',
                'integration' => 'orders_v2',
                'phase' => '7.96F5',
            ],
            [
                CommercePaymentMethod::PAYPAL,
            ]
        );
    }

    public function supports(
        CommercePaymentRequest $request
    ): bool {
        if (!$this->is_available()) {
            return false;
        }

        if (!$request->requires_payment()) {
            return false;
        }

        $capabilities = $this->get_capabilities();

        if (
            !$capabilities->supports_currency(
                $request->get_currency()
            )
        ) {
            return false;
        }

        $method =
            $request->get_preferred_payment_method();

        return $method === null
            || $capabilities->supports_payment_method(
                $method
            );
    }

    public function validate(
        CommercePaymentRequest $request
    ): CommercePaymentProviderValidationResult {
        $result =
            CommercePaymentProviderValidationResult::valid();

        if (!$this->configuration->is_enabled()) {
            $result->add_error(
                'paypal_routing_not_enabled',
                'PayPal routing remains disabled until the customer return flow is certified.'
            );
        }

        if (!$this->gateway->is_configured()) {
            $result->add_error(
                'paypal_not_configured',
                'PayPal client credentials are not configured.'
            );
        }

        if (!$request->requires_payment()) {
            $result->add_error(
                'paypal_free_payment_not_supported',
                'PayPal must not initialize zero-amount payments.'
            );
        }

        if (
            !$this->get_capabilities()->supports_currency(
                $request->get_currency()
            )
        ) {
            $result->add_error(
                'paypal_currency_not_supported',
                'PayPal does not support the requested currency.',
                [
                    'currency' =>
                        $request->get_currency(),
                ]
            );
        }

        if ($request->get_return_url() === null) {
            $result->add_error(
                'paypal_return_url_missing',
                'PayPal requires a return URL.'
            );
        }

        if ($request->get_cancel_url() === null) {
            $result->add_error(
                'paypal_cancel_url_missing',
                'PayPal requires a cancel URL.'
            );
        }

        return $result;
    }

    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        // F2 can be exercised explicitly in tests/admin tooling even while
        // automatic customer routing remains disabled.
        $validation =
            CommercePaymentProviderValidationResult::valid();

        if (!$this->gateway->is_configured()) {
            $validation->add_error(
                'paypal_not_configured',
                'PayPal client credentials are not configured.'
            );
        }
        if (!$request->requires_payment()) {
            $validation->add_error(
                'paypal_free_payment_not_supported',
                'PayPal must not initialize zero-amount payments.'
            );
        }
        if (
            !$this->get_capabilities()->supports_currency(
                $request->get_currency()
            )
        ) {
            $validation->add_error(
                'paypal_currency_not_supported',
                'PayPal does not support the requested currency.'
            );
        }
        if ($request->get_return_url() === null) {
            $validation->add_error(
                'paypal_return_url_missing',
                'PayPal requires a return URL.'
            );
        }
        if ($request->get_cancel_url() === null) {
            $validation->add_error(
                'paypal_cancel_url_missing',
                'PayPal requires a cancel URL.'
            );
        }

        if (!$validation->is_valid()) {
            throw new CommercePaymentProviderException(
                'The PayPal payment request is invalid.',
                self::KEY,
                'paypal_validation_failed',
                [
                    'validation' =>
                        $validation->to_array(),
                ]
            );
        }

        try {
            $returnurl = (string)$request->get_return_url();
            $cancelurl = $this->build_cancel_return_url(
                $returnurl
            );

            $response = $this->gateway->create_order(
                new PayPalOrderRequest(
                    $request->get_reference(),
                    $request->get_amount_minor(),
                    $request->get_currency(),
                    $request->get_customer()->get_email(),
                    $returnurl,
                    $cancelurl,
                    $context->get_idempotency_key(),
                    $request->get_metadata()
                )
            );
        } catch (\Throwable $exception) {
            throw $this->wrap_failure(
                'paypal_order_creation_failed',
                'PayPal order creation failed.',
                $exception,
                [
                    'requestreference' =>
                        $request->get_reference(),
                ]
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
        try {
            $response = $this->gateway->retrieve_order(
                $providerpaymentid
            );
        } catch (\Throwable $exception) {
            throw $this->wrap_failure(
                'paypal_order_retrieval_failed',
                'PayPal order retrieval failed.',
                $exception,
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ]
            );
        }

        return $this->map_response(
            'paypal-order:' . trim($providerpaymentid),
            $response
        );
    }

    /**
     * Capture an approved PayPal order.
     *
     * This provider-specific operation is consumed by the F3 return handler.
     */
    public function capture(
        string $providerpaymentid,
        CommercePaymentProviderContext $context,
        ?string $requestreference = null
    ): CommercePaymentResult {
        try {
            $response = $this->gateway->capture_order(
                $providerpaymentid,
                $context->get_idempotency_key()
                    . '-capture'
            );
        } catch (\Throwable $exception) {
            throw $this->wrap_failure(
                'paypal_order_capture_failed',
                'PayPal order capture failed.',
                $exception,
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ]
            );
        }

        return $this->map_response(
            $requestreference
                ?? ('paypal-order:' . trim($providerpaymentid)),
            $response
        );
    }


    public function refund(
        CommercePaymentRefundRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentRefundResult {
        if (!$this->is_available()) {
            throw new CommercePaymentProviderException(
                'PayPal is not available for Commerce refund.',
                self::KEY,
                'paypal_refund_provider_unavailable'
            );
        }

        try {
            $response = $this->gateway->refund_capture(
                $request->get_provider_payment_id(),
                $request->get_amount_minor(),
                $request->get_currency(),
                $context->get_idempotency_key(),
                $request->get_reason()
            );
        } catch (\Throwable $exception) {
            throw $this->wrap_failure(
                'paypal_refund_failed',
                'PayPal refund failed.',
                $exception,
                [
                    'captureid' =>
                        $request->get_provider_payment_id(),
                    'currency' =>
                        $request->get_currency(),
                    'amountminor' =>
                        $request->get_amount_minor(),
                ]
            );
        }

        return $this->map_refund_response(
            $response,
            [
                'paymentreference' =>
                    $request->get_payment_reference(),
                'providerpaymentid' =>
                    $request->get_provider_payment_id(),
            ]
        );
    }

    public function list_refunds(
        string $providerpaymentid,
        string $currency,
        CommercePaymentProviderContext $context
    ): array {
        // PayPal's Capture lookup exposes refund state but does not provide a
        // reliable list of Refund IDs/amounts. Exact external refunds are
        // imported from signed webhook resources in F6.1. Known refunds can
        // then be revalidated individually through GET /v2/payments/refunds/{id}.
        return [];
    }

    private function map_refund_response(
        PayPalRefundResponse $response,
        array $metadata = []
    ): CommercePaymentRefundResult {
        $status = match ($response->get_status()) {
            'COMPLETED' =>
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
            'FAILED', 'CANCELLED' =>
                CommercePaymentRefundResult::STATUS_FAILED,
            default =>
                CommercePaymentRefundResult::STATUS_PENDING,
        };

        return new CommercePaymentRefundResult(
            self::KEY,
            $response->get_refund_id(),
            $status,
            $response->get_currency(),
            $response->get_amount_minor(),
            array_merge(
                $response->get_metadata(),
                $metadata
            )
        );
    }

    public function cancel(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        throw new CommercePaymentProviderException(
            'PayPal Orders v2 cancellation is not certified in Commerce.',
            self::KEY,
            'paypal_order_cancellation_not_supported',
            [
                'providerpaymentid' =>
                    trim($providerpaymentid),
            ]
        );
    }

    private function build_cancel_return_url(
        string $returnurl
    ): string {
        $url = new \moodle_url($returnurl);
        $url->param('result', 'cancel');
        $url->param('provider', self::KEY);

        return $url->out(false);
    }

    private function map_response(
        string $requestreference,
        PayPalOrderResponse $response
    ): CommercePaymentResult {
        $status = match ($response->get_status()) {
            'CREATED',
            'SAVED',
            'APPROVED',
            'PAYER_ACTION_REQUIRED' =>
                CommercePaymentStatus::REQUIRES_ACTION,

            'COMPLETED' =>
                CommercePaymentStatus::SUCCEEDED,

            'VOIDED' =>
                CommercePaymentStatus::CANCELLED,

            default =>
                CommercePaymentStatus::PENDING,
        };

        $metadata = array_merge(
            $response->get_metadata(),
            [
                'paypal_order_status' =>
                    $response->get_status(),
                'paypal_capture_id' =>
                    $response->get_capture_id(),
            ]
        );

        if (
            $status
                === CommercePaymentStatus::REQUIRES_ACTION
        ) {
            $approvalurl =
                $response->get_approval_url();

            if ($approvalurl === null) {
                // APPROVED means the customer has already approved the order;
                // F3 may capture it without another redirect.
                if ($response->get_status() === 'APPROVED') {
                    return CommercePaymentResult::pending(
                        $requestreference,
                        self::KEY,
                        $response->get_order_id(),
                        $metadata
                    );
                }

                throw new CommercePaymentProviderException(
                    'PayPal returned an actionable order without an approval URL.',
                    self::KEY,
                    'paypal_approval_url_missing',
                    [
                        'orderid' =>
                            $response->get_order_id(),
                        'status' =>
                            $response->get_status(),
                    ]
                );
            }

            return CommercePaymentResult::requires_action(
                $requestreference,
                self::KEY,
                $response->get_order_id(),
                CommercePaymentAction::redirect(
                    $approvalurl
                ),
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

    private function wrap_failure(
        string $code,
        string $message,
        \Throwable $exception,
        array $context = []
    ): CommercePaymentProviderException {
        if (
            $exception
                instanceof CommercePaymentProviderException
        ) {
            return $exception;
        }

        return new CommercePaymentProviderException(
            $message . ' — ' . $exception->getMessage(),
            self::KEY,
            $code,
            $context,
            $exception
        );
    }
}
