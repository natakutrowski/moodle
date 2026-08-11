<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Aggregated customer metrics derived exclusively from the unified snapshot. */
final class CommerceCustomerMetrics {
    /**
     * @param array<string, int> $purchasebytype
     * @param array<string, int> $purchasebystatus
     * @param array<string, int> $paymentbystatus
     * @param array<string, int> $providerusage
     * @param array<string, int> $grantbytype
     * @param array<string, int> $revenuebycurrency
     */
    public function __construct(
        public readonly int $purchasecount,
        public readonly int $successfulpurchasecount,
        public readonly int $paymentattemptcount,
        public readonly int $successfulpaymentcount,
        public readonly int $activegrantcount,
        public readonly int $guestpurchasecount,
        public readonly array $purchasebytype,
        public readonly array $purchasebystatus,
        public readonly array $paymentbystatus,
        public readonly array $providerusage,
        public readonly array $grantbytype,
        public readonly array $revenuebycurrency,
        public readonly ?int $firstpurchaseat,
        public readonly ?int $lastpurchaseat,
        public readonly ?int $lastsuccessfulpurchaseat
    ) {}

    /** @return array<string, mixed> */
    public function to_array(): array {
        return get_object_vars($this);
    }
}
