<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent refund result.
 */
final class CommercePaymentRefundResult {
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    private const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUCCEEDED,
        self::STATUS_FAILED,
    ];

    public function __construct(
        private readonly string $providerkey,
        private readonly string $providerrefundid,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly array $metadata = []
    ) {
        if (trim($providerkey) === '') {
            throw new \coding_exception(
                'A refund result requires a provider key.'
            );
        }
        if (trim($providerrefundid) === '') {
            throw new \coding_exception(
                'A refund result requires a provider refund identifier.'
            );
        }
        if (!in_array($status, self::STATUSES, true)) {
            throw new \coding_exception(
                'Invalid Commerce refund result status.'
            );
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception(
                'A refund result currency must use ISO 4217 format.'
            );
        }
        if ($amountminor <= 0) {
            throw new \coding_exception(
                'A refund result amount must be strictly positive.'
            );
        }
    }

    public function get_provider_key(): string {
        return strtolower(trim($this->providerkey));
    }

    public function get_provider_refund_id(): string {
        return trim($this->providerrefundid);
    }

    public function get_status(): string {
        return $this->status;
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
