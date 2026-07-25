<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

final class CommerceI10fReadinessReport {
    public function __construct(
        private readonly array $counts,
        private readonly CommerceMigrationSafetyReport $safety,
        private readonly array $flagissues
    ) {
    }

    public function is_ready_for_functional_certification(): bool {
        return ($this->counts[CommerceRuntimeWriteInventory::CLASS_MIGRATION_CANDIDATE] ?? 0) === 0
            && $this->safety->is_safe_for_preprod()
            && $this->flagissues === [];
    }

    public function get_counts(): array {
        return $this->counts;
    }

    public function get_safety(): CommerceMigrationSafetyReport {
        return $this->safety;
    }

    public function get_flag_issues(): array {
        return $this->flagissues;
    }
}
