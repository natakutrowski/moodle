<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Aggregate counters for a certification batch. */
final class CommercePersistenceCertificationSummary {
    /** @var CommercePersistenceCertificationResult[] */
    private array $results = [];

    public function add(CommercePersistenceCertificationResult $result): void {
        $this->results[] = $result;
    }

    /** @return CommercePersistenceCertificationResult[] */
    public function get_results(): array { return $this->results; }
    public function get_total(): int { return count($this->results); }

    public function count_status(string $status): int {
        return count(array_filter(
            $this->results,
            static fn(CommercePersistenceCertificationResult $result): bool => $result->get_status() === $status
        ));
    }

    public function get_certified(): int { return $this->count_status(CommercePersistenceCertificationResult::STATUS_CERTIFIED); }
    public function get_different(): int { return $this->count_status(CommercePersistenceCertificationResult::STATUS_DIFFERENT); }
    public function get_failed(): int { return $this->count_status(CommercePersistenceCertificationResult::STATUS_FAILED); }
    public function get_skipped(): int { return $this->count_status(CommercePersistenceCertificationResult::STATUS_SKIPPED); }
    public function has_failures(): bool { return $this->get_different() > 0 || $this->get_failed() > 0; }
}
