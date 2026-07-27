<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\execution;

defined('MOODLE_INTERNAL') || die();

/**
 * Summary of one idempotent Native Commerce entitlement ledger execution.
 */
final class CommerceEntitlementLedgerExecutionResult {
    public function __construct(
        private readonly int $created,
        private readonly int $identical,
        private readonly int $applied,
        private readonly int $skipped,
        private readonly int $failed
    ) {
    }

    public function get_created(): int {
        return $this->created;
    }

    public function get_identical(): int {
        return $this->identical;
    }

    public function get_applied(): int {
        return $this->applied;
    }

    public function get_skipped(): int {
        return $this->skipped;
    }

    public function get_failed(): int {
        return $this->failed;
    }

    public function is_successful(): bool {
        return $this->failed === 0;
    }
}
