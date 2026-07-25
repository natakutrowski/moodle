<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\dto;

defined('MOODLE_INTERNAL') || die();

/** Immutable read model for one Commerce fulfillment operation. */
final class CommerceFulfillmentView {
    public function __construct(
        public readonly int $sequence,
        public readonly string $reference,
        public readonly string $fulfillmentkey,
        public readonly string $idempotencykey,
        public readonly string $status,
        public readonly array $metadata = []
    ) {
    }
}
