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
        public readonly int $dateto = 0,
        public readonly string $sort = 'date',
        public readonly string $direction = 'desc',
        public readonly string $offerorigin = '',
        public readonly string $adminstate = 'open'
    ) {
    }

    public function normalized_query(): string {
        return trim($this->query);
    }

    public function normalized_sort(): string {
        return in_array($this->sort, [
            'date', 'reference', 'customer', 'type', 'product', 'amount',
            'payment', 'fulfillment', 'commercial',
        ], true) ? $this->sort : 'date';
    }

    public function normalized_direction(): string {
        return strtolower($this->direction) === 'asc' ? 'asc' : 'desc';
    }


    public function normalized_admin_state(): string {
        return in_array($this->adminstate, ['open', 'closed', 'all'], true)
            ? $this->adminstate
            : 'open';
    }

    public function normalized_offer_origin(): string {
        return in_array($this->offerorigin, ['personaloffer', 'standard'], true)
            ? $this->offerorigin
            : '';
    }
}
