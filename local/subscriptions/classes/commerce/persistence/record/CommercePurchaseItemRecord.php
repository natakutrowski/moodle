<?php

namespace local_subscriptions\commerce\persistence\record;

defined('MOODLE_INTERNAL') || die();

/** Immutable database-neutral record for one Commerce purchase line. */
final class CommercePurchaseItemRecord {
    public function __construct(
        private readonly string $purchaseuuid,
        private readonly int $position,
        private readonly string $itemtype,
        private readonly string $itemreference,
        private readonly string $label,
        private readonly int $quantity,
        private readonly string $currency,
        private readonly int $unitminor,
        private readonly int $grossminor,
        private readonly int $discountminor,
        private readonly int $netminor,
        private readonly string $pricingjson,
        private readonly string $fulfillmentjson,
        private readonly string $metadatajson
    ) {
        if ($position < 0 || $quantity <= 0) {
            throw new \coding_exception('Invalid persisted Commerce purchase item position or quantity.');
        }
        if ($unitminor < 0 || $grossminor < 0 || $discountminor < 0 || $netminor < 0) {
            throw new \coding_exception('Persisted Commerce item amounts cannot be negative.');
        }
        if ($unitminor * $quantity !== $grossminor || $grossminor - $discountminor !== $netminor) {
            throw new \coding_exception('Persisted Commerce item totals are inconsistent.');
        }
    }

    public function get_purchase_uuid(): string { return $this->purchaseuuid; }
    public function get_position(): int { return $this->position; }

    public function to_record(): \stdClass {
        return (object)get_object_vars($this);
    }
}
