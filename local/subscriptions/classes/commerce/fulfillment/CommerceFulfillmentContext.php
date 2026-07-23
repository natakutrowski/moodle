<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable payment confirmation context used by post-payment fulfillment.
 */
final class CommerceFulfillmentContext {

    public function __construct(
        private readonly string $reference,
        private readonly bool $paymentconfirmed,
        private readonly string $provider,
        private readonly string $transactionid,
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly int $paidat,
        private readonly ?int $paymentrequestid = null,
        private readonly string $source = 'unknown',
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Commerce fulfillment context reference cannot be empty.'
            );
        }

        if ($amountminor < 0) {
            throw new \coding_exception(
                'A Commerce fulfillment amount cannot be negative.'
            );
        }

        if ($paidat <= 0) {
            throw new \coding_exception(
                'A Commerce fulfillment payment timestamp must be positive.'
            );
        }

        if ($paymentrequestid !== null && $paymentrequestid <= 0) {
            throw new \coding_exception(
                'A Commerce fulfillment payment request identifier must be positive.'
            );
        }
    }

    public static function confirmed(
        string $reference,
        string $provider,
        string $transactionid,
        int $amountminor,
        string $currency,
        int $paidat,
        ?int $paymentrequestid = null,
        string $source = 'unknown',
        array $metadata = []
    ): self {
        return new self(
            $reference,
            true,
            $provider,
            $transactionid,
            $amountminor,
            $currency,
            $paidat,
            $paymentrequestid,
            $source,
            $metadata
        );
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function is_payment_confirmed(): bool {
        return $this->paymentconfirmed;
    }

    public function get_provider(): string {
        return strtolower(trim($this->provider));
    }

    public function get_transaction_id(): string {
        return trim($this->transactionid);
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_amount_major(): float {
        return round($this->amountminor / 100, 2);
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_paid_at(): int {
        return $this->paidat;
    }

    public function get_payment_request_id(): ?int {
        return $this->paymentrequestid;
    }

    public function get_source(): string {
        return strtolower(trim($this->source));
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
