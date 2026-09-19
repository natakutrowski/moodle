<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

/**
 * Persisted native Commerce refund operation.
 */
final class CommercePaymentRefundRecord {
    public function __construct(
        private readonly int $id,
        private readonly int $paymentid,
        private readonly string $provider,
        private readonly ?string $providerrefundid,
        private readonly string $idempotencykey,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $reason,
        private readonly array $metadata,
        private readonly ?array $providerpayload,
        private readonly ?int $createdby,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {}

    public function get_id(): int { return $this->id; }
    public function get_payment_id(): int { return $this->paymentid; }
    public function get_provider(): string { return $this->provider; }
    public function get_provider_refund_id(): ?string { return $this->providerrefundid; }
    public function get_idempotency_key(): string { return $this->idempotencykey; }
    public function get_status(): string { return $this->status; }
    public function get_currency(): string { return $this->currency; }
    public function get_amount_minor(): int { return $this->amountminor; }
    public function get_reason(): ?string { return $this->reason; }
    public function get_metadata(): array { return $this->metadata; }
    public function get_provider_payload(): ?array { return $this->providerpayload; }
    public function get_created_by(): ?int { return $this->createdby; }
    public function get_time_created(): int { return $this->timecreated; }
    public function get_time_modified(): int { return $this->timemodified; }

    public function is_counted_against_refundable_amount(): bool {
        return in_array(
            $this->status,
            [
                CommercePaymentRefundResult::STATUS_PENDING,
                CommercePaymentRefundResult::STATUS_SUCCEEDED,
            ],
            true
        );
    }
}
