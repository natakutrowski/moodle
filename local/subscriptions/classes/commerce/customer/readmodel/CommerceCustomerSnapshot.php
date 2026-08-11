<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Unified Native Commerce customer snapshot used by CRM and User 360. */
final class CommerceCustomerSnapshot {
    /**
     * @param CommerceCustomerPurchase[] $purchases
     * @param CommerceCustomerPayment[] $payments
     * @param CommerceCustomerGrant[] $grants
     */
    public function __construct(
        public readonly CommerceCustomerIdentity $identity,
        public readonly array $purchases,
        public readonly array $payments,
        public readonly array $grants,
        public readonly CommerceCustomerMetrics $metrics
    ) {}

    public function has_purchases(): bool {
        return $this->purchases !== [];
    }

    public function latest_purchase(): ?CommerceCustomerPurchase {
        return $this->purchases[0] ?? null;
    }

    public function latest_successful_purchase(): ?CommerceCustomerPurchase {
        foreach ($this->purchases as $purchase) {
            if ($purchase->has_successful_payment()) {
                return $purchase;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'identity' => $this->identity->to_array(),
            'purchases' => array_map(static fn(CommerceCustomerPurchase $purchase): array => $purchase->to_array(), $this->purchases),
            'payments' => array_map(static fn(CommerceCustomerPayment $payment): array => $payment->to_array(), $this->payments),
            'grants' => array_map(static fn(CommerceCustomerGrant $grant): array => $grant->to_array(), $this->grants),
            'metrics' => $this->metrics->to_array(),
        ];
    }
}
