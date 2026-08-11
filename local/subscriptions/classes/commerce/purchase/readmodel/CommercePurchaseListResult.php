<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Paginated result for the unified purchase list. */
final class CommercePurchaseListResult {
    /** @param CommercePurchaseSummary[] $purchases */
    public function __construct(
        public readonly array $purchases,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perpage
    ) {
    }
}
