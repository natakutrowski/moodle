<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalRefundResponse {
    public function __construct(
        private readonly string $refundid,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly array $metadata = []
    ) {
        if (trim($refundid) === '') {
            throw new \coding_exception(
                'A PayPal refund response requires a refund id.'
            );
        }

        if (trim($status) === '') {
            throw new \coding_exception(
                'A PayPal refund response requires a status.'
            );
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception(
                'A PayPal refund response requires an ISO currency.'
            );
        }

        if ($amountminor <= 0) {
            throw new \coding_exception(
                'A PayPal refund response requires a positive amount.'
            );
        }
    }

    public function get_refund_id(): string {
        return trim($this->refundid);
    }

    public function get_status(): string {
        return strtoupper(trim($this->status));
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
