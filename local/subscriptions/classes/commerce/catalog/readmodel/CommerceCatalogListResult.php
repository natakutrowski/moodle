<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Paginated result for the catalogue administration list. */
final class CommerceCatalogListResult {
    /** @param CommerceCatalogProductSummary[] $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perpage
    ) {
    }
}
