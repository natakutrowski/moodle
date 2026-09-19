<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

final class AlfaRefundRequest {
    public function __construct(
        private readonly string $paymentreference,
        private readonly string $providerpaymentid,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $reason = null,
        private readonly array $metadata = []
    ) {
        if (trim($providerpaymentid) === '') {
            throw new \coding_exception('An Alfa refund requires an order identifier.');
        }
        if (strtoupper(trim($currency)) !== 'RUB') {
            throw new \coding_exception('The current Alfa integration refunds RUB payments only.');
        }
        if ($amountminor <= 0) {
            throw new \coding_exception('An Alfa refund amount must be positive.');
        }
    }

    public function get_payment_reference(): string {
        return trim($this->paymentreference);
    }

    public function get_provider_payment_id(): string {
        return trim($this->providerpaymentid);
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_reason(): ?string {
        $reason = trim((string)$this->reason);
        return $reason !== '' ? $reason : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
