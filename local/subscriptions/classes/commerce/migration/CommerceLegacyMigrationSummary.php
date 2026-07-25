<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Aggregate counters for one migration execution. */
final class CommerceLegacyMigrationSummary {
    /** @var CommerceLegacyMigrationResult[] */
    private array $results = [];

    public function add(CommerceLegacyMigrationResult $result): void {
        $this->results[] = $result;
    }

    /** @return CommerceLegacyMigrationResult[] */
    public function get_results(): array { return $this->results; }
    public function get_total(): int { return count($this->results); }

    public function count_status(string $status): int {
        return count(array_filter(
            $this->results,
            static fn(CommerceLegacyMigrationResult $result): bool => $result->get_status() === $status
        ));
    }

    public function get_migrated(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_MIGRATED); }
    public function get_already_present(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT); }
    public function get_dry_run(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_DRY_RUN); }
    public function get_skipped(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_SKIPPED); }
    public function get_invalid(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_INVALID); }
    public function get_failed(): int { return $this->count_status(CommerceLegacyMigrationResult::STATUS_FAILED); }

    public function has_failures(): bool {
        return $this->get_invalid() > 0 || $this->get_failed() > 0;
    }

    public function to_array(): array {
        return [
            'total' => $this->get_total(),
            'migrated' => $this->get_migrated(),
            'already_present' => $this->get_already_present(),
            'dry_run' => $this->get_dry_run(),
            'skipped' => $this->get_skipped(),
            'invalid' => $this->get_invalid(),
            'failed' => $this->get_failed(),
        ];
    }
}
