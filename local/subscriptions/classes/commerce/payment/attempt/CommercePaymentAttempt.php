<?php

namespace local_subscriptions\commerce\payment\attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * One native payment attempt initiated for a Commerce purchase.
 *
 * A purchase may own several attempts. Provider callbacks update only the
 * matching attempt; they never rewrite the immutable commercial snapshot.
 */
final class CommercePaymentAttempt {

    public function __construct(
        private readonly ?int $id,
        private readonly string $purchaseuuid,
        private readonly int $sequence,
        private readonly string $provider,
        private readonly string $status,
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly ?string $providerreference = null,
        private readonly ?string $providerorderid = null,
        private readonly ?string $transactionid = null,
        private readonly ?int $legacyrequestid = null,
        private readonly ?string $paymenturl = null,
        private readonly array $metadata = [],
        private readonly ?array $providerpayload = null,
        private readonly ?int $paidat = null,
        private readonly ?int $timecreated = null,
        private readonly ?int $timemodified = null
    ) {
        if ($id !== null && $id <= 0) {
            throw new \InvalidArgumentException('A Commerce payment attempt identifier must be positive.');
        }

        if (!preg_match('/^[a-f0-9]{32}$/', $purchaseuuid)) {
            throw new \InvalidArgumentException('A Commerce purchase UUID must contain 32 lowercase hexadecimal characters.');
        }

        if ($sequence < 0) {
            throw new \InvalidArgumentException('A Commerce payment attempt sequence cannot be negative.');
        }

        if (trim($provider) === '') {
            throw new \InvalidArgumentException('A Commerce payment provider cannot be empty.');
        }

        if ($amountminor < 0) {
            throw new \InvalidArgumentException('A Commerce payment amount cannot be negative.');
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper($currency))) {
            throw new \InvalidArgumentException('A Commerce payment currency must be a three-letter ISO code.');
        }

        if ($legacyrequestid !== null && $legacyrequestid <= 0) {
            throw new \InvalidArgumentException('A Legacy payment request identifier must be positive.');
        }

        CommercePaymentAttemptStatus::normalise($status);
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_purchase_uuid(): string {
        return $this->purchaseuuid;
    }

    public function get_sequence(): int {
        return $this->sequence;
    }

    public function get_provider(): string {
        return strtolower(trim($this->provider));
    }

    public function get_status(): string {
        return CommercePaymentAttemptStatus::normalise($this->status);
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_currency(): string {
        return strtoupper($this->currency);
    }

    public function get_provider_reference(): ?string {
        return $this->normalise_nullable_string($this->providerreference);
    }

    public function get_provider_order_id(): ?string {
        return $this->normalise_nullable_string($this->providerorderid);
    }

    public function get_transaction_id(): ?string {
        return $this->normalise_nullable_string($this->transactionid);
    }

    public function get_legacy_request_id(): ?int {
        return $this->legacyrequestid;
    }

    public function get_payment_url(): ?string {
        return $this->normalise_nullable_string($this->paymenturl);
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_provider_payload(): ?array {
        return $this->providerpayload;
    }

    public function get_paid_at(): ?int {
        return $this->paidat;
    }

    public function get_time_created(): ?int {
        return $this->timecreated;
    }

    public function get_time_modified(): ?int {
        return $this->timemodified;
    }

    private function normalise_nullable_string(?string $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
