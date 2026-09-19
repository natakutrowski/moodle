<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

interface PayPalPaymentGateway {
    public function is_configured(): bool;

    public function create_order(
        PayPalOrderRequest $request
    ): PayPalOrderResponse;

    public function retrieve_order(
        string $orderid
    ): PayPalOrderResponse;

    public function capture_order(
        string $orderid,
        string $idempotencykey
    ): PayPalOrderResponse;

    public function refund_capture(
        string $captureid,
        int $amountminor,
        string $currency,
        string $idempotencykey,
        ?string $note = null
    ): PayPalRefundResponse;

    public function retrieve_capture(
        string $captureid
    ): array;

    public function retrieve_refund(
        string $refundid
    ): PayPalRefundResponse;
}
