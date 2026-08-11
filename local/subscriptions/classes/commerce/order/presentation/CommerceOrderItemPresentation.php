<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Immutable customer-facing representation of one Native purchase item. */
final class CommerceOrderItemPresentation {
    public function __construct(
        public readonly string $reference,
        public readonly string $type,
        public readonly string $label,
        public readonly int $quantity,
        public readonly string $currency,
        public readonly int $unitminor,
        public readonly int $grossminor,
        public readonly int $discountminor,
        public readonly int $netminor,
        public readonly array $accesses = [],
        public readonly array $metadata = []
    ) {
    }
}
