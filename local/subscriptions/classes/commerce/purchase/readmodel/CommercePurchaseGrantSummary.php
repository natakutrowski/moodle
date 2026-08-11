<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Native entitlement grant attached to a purchase. */
final class CommercePurchaseGrantSummary {
    public function __construct(
        public readonly string $reference,
        public readonly string $itemreference,
        public readonly string $productsku,
        public readonly string $type,
        public readonly string $resourcekey,
        public readonly int $quantity,
        public readonly string $status,
        public readonly ?int $beneficiaryuserid,
        public readonly string $beneficiaryemail,
        public readonly int $validfrom,
        public readonly ?int $validuntil,
        public readonly array $configuration = [],
        public readonly array $metadata = []
    ) {
    }
}
