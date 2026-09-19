<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

final class StripeRefundResponse {
    public function __construct(
        private readonly string $refundid,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly array $metadata = []
    ) {}
    public function get_refund_id(): string { return $this->refundid; }
    public function get_status(): string { return $this->status; }
    public function get_currency(): string { return $this->currency; }
    public function get_amount_minor(): int { return $this->amountminor; }
    public function get_metadata(): array { return $this->metadata; }
}
