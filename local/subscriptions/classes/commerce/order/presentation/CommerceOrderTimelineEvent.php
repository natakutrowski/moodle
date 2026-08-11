<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** One safe, customer-facing event in an order timeline. */
final class CommerceOrderTimelineEvent {
    public function __construct(
        public readonly string $type,
        public readonly string $status,
        public readonly int $timestamp,
        public readonly string $label,
        public readonly array $metadata = []
    ) {
    }
}
