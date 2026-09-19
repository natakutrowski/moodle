<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\creditnote;

defined('MOODLE_INTERNAL') || die();

/** Immutable persisted Commerce credit-note identity and historical snapshots. */
final class CommerceIssuedCreditNote {
    public function __construct(
        public readonly int $id,
        public readonly int $refundid,
        public readonly int $invoiceid,
        public readonly int $purchaseid,
        public readonly string $number,
        public readonly string $entitykey,
        public readonly int $sequence,
        public readonly int $year,
        public readonly int $issuedat,
        public readonly array $seller,
        public readonly array $customer,
        public readonly array $financial,
        public readonly array $metadata
    ) {}
}
