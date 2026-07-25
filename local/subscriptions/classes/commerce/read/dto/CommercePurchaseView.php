<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\dto;

defined('MOODLE_INTERNAL') || die();

/** Immutable read model for one native Commerce purchase. */
final class CommercePurchaseView {
    public function __construct(
        public readonly string $uuid,
        public readonly string $reference,
        public readonly string $type,
        public readonly ?string $legacyfamily,
        public readonly ?int $legacyid,
        public readonly ?int $userid,
        public readonly ?string $customeremail,
        public readonly string $status,
        public readonly string $currency,
        public readonly int $subtotalminor,
        public readonly int $discountminor,
        public readonly int $totalminor,
        public readonly int $timecreated,
        public readonly int $timemodified,
        public readonly array $items,
        public readonly array $payments,
        public readonly array $fulfillments,
        public readonly string $source = 'native'
    ) {
    }
}
