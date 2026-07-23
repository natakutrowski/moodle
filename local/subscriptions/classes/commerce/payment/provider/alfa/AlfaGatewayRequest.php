<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised payment request passed to the Alfa Legacy gateway.
 */
final class AlfaGatewayRequest {

    public function __construct(
        private readonly string $ordernumber,
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly string $description,
        private readonly string $customeremail,
        private readonly string $returnurl,
        private readonly string $failurl,
        private readonly string $idempotencykey,
        private readonly array $metadata = []
    ) {
        if (trim($ordernumber) === '') {
            throw new \coding_exception(
                'An Alfa order number cannot be empty.'
            );
        }

        if ($amountminor <= 0) {
            throw new \coding_exception(
                'An Alfa payment amount must be positive.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                strtoupper(trim($currency))
            )
        ) {
            throw new \coding_exception(
                'An Alfa currency must use ISO 4217 format.'
            );
        }

        if (trim($description) === '') {
            throw new \coding_exception(
                'An Alfa payment description cannot be empty.'
            );
        }

        if (!validate_email(trim($customeremail))) {
            throw new \coding_exception(
                'An Alfa payment requires a valid customer email.'
            );
        }

        if (
            trim($returnurl) === ''
            || trim($failurl) === ''
        ) {
            throw new \coding_exception(
                'Alfa return and failure URLs are required.'
            );
        }

        if (trim($idempotencykey) === '') {
            throw new \coding_exception(
                'An Alfa idempotency key is required.'
            );
        }
    }

    public function get_order_number(): string {
        return trim($this->ordernumber);
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_description(): string {
        return trim($this->description);
    }

    public function get_customer_email(): string {
        return \core_text::strtolower(
            trim($this->customeremail)
        );
    }

    public function get_return_url(): string {
        return trim($this->returnurl);
    }

    public function get_fail_url(): string {
        return trim($this->failurl);
    }

    public function get_idempotency_key(): string {
        return trim($this->idempotencykey);
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function to_array(): array {
        return [
            'ordernumber' => $this->get_order_number(),
            'amountminor' => $this->get_amount_minor(),
            'currency' => $this->get_currency(),
            'description' => $this->get_description(),
            'customeremail' => $this->get_customer_email(),
            'returnurl' => $this->get_return_url(),
            'failurl' => $this->get_fail_url(),
            'idempotencykey' => $this->get_idempotency_key(),
            'metadata' => $this->get_metadata(),
        ];
    }
}