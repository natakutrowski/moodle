<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised request passed from Commerce to the Stripe Legacy gateway.
 */
final class StripeGatewayRequest {

    public function __construct(
        private readonly string $reference,
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly string $customeremail,
        private readonly array $lines,
        private readonly string $successurl,
        private readonly string $cancelurl,
        private readonly string $idempotencykey,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Stripe gateway request reference cannot be empty.'
            );
        }

        if ($amountminor <= 0) {
            throw new \coding_exception(
                'A Stripe gateway request amount must be positive.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                strtoupper(trim($currency))
            )
        ) {
            throw new \coding_exception(
                'A Stripe gateway currency must use ISO 4217 format.'
            );
        }

        if (!validate_email(trim($customeremail))) {
            throw new \coding_exception(
                'A Stripe gateway request requires a valid customer email.'
            );
        }

        if ($lines === []) {
            throw new \coding_exception(
                'A Stripe gateway request requires at least one line.'
            );
        }

        if (trim($successurl) === '' || trim($cancelurl) === '') {
            throw new \coding_exception(
                'Stripe success and cancel URLs are required.'
            );
        }

        if (trim($idempotencykey) === '') {
            throw new \coding_exception(
                'A Stripe idempotency key is required.'
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

    public function get_lines(): array {
        return $this->lines;
    }

    public function get_success_url(): string {
        return trim($this->successurl);
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

    public function to_array(): array {
        return [
            'reference' => $this->get_reference(),
            'amountminor' => $this->get_amount_minor(),
            'currency' => $this->get_currency(),
            'customeremail' => $this->get_customer_email(),
            'lines' => $this->get_lines(),
            'successurl' => $this->get_success_url(),
            'cancelurl' => $this->get_cancel_url(),
            'idempotencykey' => $this->get_idempotency_key(),
            'metadata' => $this->get_metadata(),
        ];
    }
}