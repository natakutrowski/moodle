<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\dto;

defined('MOODLE_INTERNAL') || die();

/** Read projection of an entitlement declared by a purchase item. */
final class CommerceEntitlementView {
    public function __construct(
        public readonly string $purchaseuuid,
        public readonly string $itemtype,
        public readonly string $itemreference,
        public readonly string $label,
        public readonly array $instructions,
        public readonly string $source = 'native'
    ) {
    }
}
