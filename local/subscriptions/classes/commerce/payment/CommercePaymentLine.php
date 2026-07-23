<?php

namespace local_subscriptions\commerce\payment;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent immutable payment line.
 */
final class CommercePaymentLine {

    public function __construct(
        private readonly string $reference,
        private readonly string $description,
        private readonly int $quantity,
        private readonly int $unitamountminor,
        private readonly string $currency,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Commerce payment line reference cannot be empty.'
            );
        }

        if (trim($description) === '') {
            throw new \coding_exception(
                'A Commerce payment line description cannot be empty.'
            );
        }

        if ($quantity <= 0) {
            throw new \coding_exception(
                'A Commerce payment line quantity must be positive.'
            );
        }

        if ($unitamountminor < 0) {
            throw new \coding_exception(
                'A Commerce payment line amount cannot be negative.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                strtoupper(trim($currency))
            )
        ) {
            throw new \coding_exception(
                'A Commerce payment line currency must use ISO 4217 format.'
            );
        }
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function get_description(): string {
        return trim($this->description);
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_unit_amount_minor(): int {
        return $this->unitamountminor;
    }

    public function get_total_amount_minor(): int {
        return $this->unitamountminor
            * $this->quantity;
    }

    public function get_currency(): string {
        return strtoupper(
            trim($this->currency)
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_free(): bool {
        return $this->get_total_amount_minor() === 0;
    }

    public function to_array(): array {
        return [
            'reference' =>
                $this->get_reference(),

            'description' =>
                $this->get_description(),

            'quantity' =>
                $this->get_quantity(),

            'unitamountminor' =>
                $this->get_unit_amount_minor(),

            'totalamountminor' =>
                $this->get_total_amount_minor(),

            'currency' =>
                $this->get_currency(),

            'metadata' =>
                $this->get_metadata(),
        ];
    }
}