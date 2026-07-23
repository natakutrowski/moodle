<?php

namespace local_subscriptions\commerce\payment;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent request to initialize a Commerce payment.
 */
final class CommercePaymentRequest {

    /**
     * @param CommercePaymentLine[] $lines
     */
    public function __construct(
        private readonly string $reference,
        private readonly CommercePaymentCustomer $customer,
        private readonly array $lines,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $preferredprovider = null,
        private readonly ?string $returnurl = null,
        private readonly ?string $cancelurl = null,
        private readonly array $metadata = [],
        private readonly ?int $createdat = null
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Commerce payment request reference cannot be empty.'
            );
        }

        if ($lines === []) {
            throw new \coding_exception(
                'A Commerce payment request must contain at least one line.'
            );
        }

        foreach ($lines as $line) {
            if (!$line instanceof CommercePaymentLine) {
                throw new \coding_exception(
                    'A Commerce payment request contains an invalid line.'
                );
            }
        }

        if ($amountminor < 0) {
            throw new \coding_exception(
                'A Commerce payment request amount cannot be negative.'
            );
        }

        $currency = strtoupper(
            trim($currency)
        );

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                $currency
            )
        ) {
            throw new \coding_exception(
                'A Commerce payment request currency must use ISO 4217 format.'
            );
        }

        $this->validate_lines(
            $lines,
            $currency,
            $amountminor
        );
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function get_customer():
        CommercePaymentCustomer {
        return $this->customer;
    }

    /**
     * @return CommercePaymentLine[]
     */
    public function get_lines(): array {
        return $this->lines;
    }

    public function get_currency(): string {
        return strtoupper(
            trim($this->currency)
        );
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_preferred_provider(): ?string {
        if ($this->preferredprovider === null) {
            return null;
        }

        $provider = strtolower(
            trim($this->preferredprovider)
        );

        return $provider !== ''
            ? $provider
            : null;
    }

    public function get_return_url(): ?string {
        return $this->normalise_nullable_string(
            $this->returnurl
        );
    }

    public function get_cancel_url(): ?string {
        return $this->normalise_nullable_string(
            $this->cancelurl
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }

    public function requires_payment(): bool {
        return $this->amountminor > 0;
    }

    public function is_free(): bool {
        return !$this->requires_payment();
    }

    public function contains_multiple_lines(): bool {
        return count($this->lines) > 1;
    }

    private function validate_lines(
        array $lines,
        string $currency,
        int $amountminor
    ): void {
        $calculatedtotal = 0;

        foreach ($lines as $line) {
            if (
                $line->get_currency()
                !== $currency
            ) {
                throw new \coding_exception(
                    'A Commerce payment request cannot mix currencies.'
                );
            }

            $calculatedtotal +=
                $line->get_total_amount_minor();
        }

        if ($calculatedtotal !== $amountminor) {
            throw new \coding_exception(
                sprintf(
                    'Commerce payment amount mismatch: expected %d, calculated %d.',
                    $amountminor,
                    $calculatedtotal
                )
            );
        }
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}