<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\invoice;

defined('MOODLE_INTERNAL') || die();

/** Immutable persisted invoice identity and snapshots. */
final class CommerceIssuedInvoice {
    public function __construct(
        public readonly int $id,
        public readonly int $purchaseid,
        public readonly string $number,
        public readonly string $entitykey,
        public readonly int $sequence,
        public readonly int $year,
        public readonly int $issuedat,
        public readonly array $seller,
        public readonly array $customer,
        public readonly array $financial
    ) {}
}
