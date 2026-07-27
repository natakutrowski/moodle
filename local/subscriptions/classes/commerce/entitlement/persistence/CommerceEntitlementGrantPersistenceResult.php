<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\persistence;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable summary of one idempotent grant-plan persistence operation.
 */
final class CommerceEntitlementGrantPersistenceResult {
    public function __construct(
        private readonly int $created,
        private readonly int $identical,
        private readonly int $conflicts,
        private readonly array $records
    ) {
        if ($created < 0 || $identical < 0 || $conflicts < 0) {
            throw new \coding_exception('Native Commerce entitlement persistence counters cannot be negative.');
        }
    }

    public function get_created(): int {
        return $this->created;
    }

    public function get_identical(): int {
        return $this->identical;
    }

    public function get_conflicts(): int {
        return $this->conflicts;
    }

    public function get_records(): array {
        return $this->records;
    }

    public function is_successful(): bool {
        return $this->conflicts === 0;
    }
}
