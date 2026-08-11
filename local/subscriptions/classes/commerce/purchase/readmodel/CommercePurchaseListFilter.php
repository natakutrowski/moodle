<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable filters accepted by the unified Native purchase list. */
final class CommercePurchaseListFilter {
    public function __construct(
        public readonly string $query = '',
        public readonly string $type = '',
        public readonly string $commercialstatus = '',
        public readonly string $paymentstatus = '',
        public readonly string $fulfillmentstatus = '',
        public readonly string $provider = '',
        public readonly string $currency = '',
        public readonly int $datefrom = 0,
        public readonly int $dateto = 0
    ) {
    }

    public function normalized_query(): string {
        return trim($this->query);
    }
}
