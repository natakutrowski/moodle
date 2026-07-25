<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\observability;

defined('MOODLE_INTERNAL') || die();

/** Immutable observation emitted for one I10C read decision. */
final class CommerceReadObservation {
    public function __construct(
        public readonly string $consumer,
        public readonly string $family,
        public readonly int $legacyid,
        public readonly string $source,
        public readonly bool $success,
        public readonly bool $shadowcompared,
        public readonly ?string $shadowseverity,
        public readonly int $durationms
    ) {
    }
}
