<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

final class StripeRefundRequest {
    public function __construct(
        private readonly string $paymentreference,
        private readonly string $providerpaymentid,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $reason = null,
        private readonly array $metadata = []
    ) {}
    public function get_payment_reference(): string { return $this->paymentreference; }
    public function get_provider_payment_id(): string { return $this->providerpaymentid; }
    public function get_currency(): string { return $this->currency; }
    public function get_amount_minor(): int { return $this->amountminor; }
    public function get_reason(): ?string { return $this->reason; }
    public function get_metadata(): array { return $this->metadata; }
}
