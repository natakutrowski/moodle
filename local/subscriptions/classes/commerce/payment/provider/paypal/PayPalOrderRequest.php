<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalOrderRequest {
    public function __construct(
        private readonly string $reference,
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly string $customeremail,
        private readonly string $returnurl,
        private readonly string $cancelurl,
        private readonly string $idempotencykey,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A PayPal order request reference is required.'
            );
        }
        if ($amountminor <= 0) {
            throw new \coding_exception(
                'A PayPal order amount must be positive.'
            );
        }
        if (!validate_email(trim($customeremail))) {
            throw new \coding_exception(
                'A PayPal order requires a valid customer email.'
            );
        }
        if (
            trim($returnurl) === ''
            || trim($cancelurl) === ''
        ) {
            throw new \coding_exception(
                'PayPal return and cancel URLs are required.'
            );
        }
        if (trim($idempotencykey) === '') {
            throw new \coding_exception(
                'A PayPal request id is required.'
            );
        }
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_customer_email(): string {
        return \core_text::strtolower(
            trim($this->customeremail)
        );
    }

    public function get_return_url(): string {
        return trim($this->returnurl);
    }

    public function get_cancel_url(): string {
        return trim($this->cancelurl);
    }

    public function get_idempotency_key(): string {
        return trim($this->idempotencykey);
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
