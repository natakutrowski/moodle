<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Immutable product-level commercial statistics row. */
final class CommerceProductStatisticsRow {
    public function __construct(
        public readonly string $reference,
        public readonly string $label,
        public readonly string $itemtype,
        public readonly string $currency,
        public readonly int $orders,
        public readonly int $quantity,
        public readonly int $paidorders,
        public readonly int $freeorders,
        public readonly int $revenueminor
    ) {
    }
}
