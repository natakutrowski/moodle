<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent request to refund a Commerce payment.
 */
final class CommercePaymentRefundRequest {
    public function __construct(
        private readonly string $paymentreference,
        private readonly string $providerpaymentid,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $reason = null,
        private readonly array $metadata = []
    ) {
        if (trim($paymentreference) === '') {
            throw new \coding_exception(
                'A refund requires a Commerce payment reference.'
            );
        }

        if (trim($providerpaymentid) === '') {
            throw new \coding_exception(
                'A refund requires a provider payment identifier.'
            );
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception(
                'A refund currency must use ISO 4217 format.'
            );
        }

        if ($amountminor <= 0) {
            throw new \coding_exception(
                'A refund amount must be strictly positive.'
            );
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

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
