<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\analytics;

defined('MOODLE_INTERNAL') || die();

/** Immutable Native Commerce aggregates for one reporting period. */
final class CommerceCustomerAnalyticsSnapshot {
    /**
     * @param array<string,int> $purchasesbytype
     * @param array<string,int> $purchasesbystatus
     * @param array<string,int> $revenuebycurrency Minor units.
     * @param array<string,array<string,int>> $revenuebytypecurrency Minor units.
     */
    public function __construct(
        public readonly int $start,
        public readonly int $end,
        public readonly int $purchasecount,
        public readonly int $successfulpurchasecount,
        public readonly int $failedpurchasecount,
        public readonly int $newcustomercount,
        public readonly int $digitalbuyercount,
        public readonly int $guestpurchasecount,
        public readonly int $attachedguestcount,
        public readonly int $fulfilledpurchasecount,
        public readonly array $purchasesbytype,
        public readonly array $purchasesbystatus,
        public readonly array $revenuebycurrency,
        public readonly array $revenuebytypecurrency
    ) {}

    public function has_activity(): bool {
        return $this->purchasecount > 0;
    }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return get_object_vars($this);
    }
}
