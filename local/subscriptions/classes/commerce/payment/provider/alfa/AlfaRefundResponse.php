<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

final class AlfaRefundResponse {
    public function __construct(
        private readonly string $refundid,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly array $metadata = []
    ) {
        if (trim($refundid) === '') {
            throw new \coding_exception('An Alfa refund response requires an identifier.');
        }
        if ($amountminor <= 0) {
            throw new \coding_exception('An Alfa refund response amount must be positive.');
        }
    }

    public function get_refund_id(): string {
        return trim($this->refundid);
    }

    public function get_status(): string {
        return strtolower(trim($this->status));
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
