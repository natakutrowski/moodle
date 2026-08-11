<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\certification;

defined('MOODLE_INTERNAL') || die();

/** Read-only certification report for My Courses and Native upgrades. */
final class CommerceMyCoursesCertificationReport {
    /** @var CommerceMyCoursesCertificationFinding[] */
    private array $findings = [];

    public function add(CommerceMyCoursesCertificationFinding $finding): void {
        $this->findings[] = $finding;
    }

    /** @return CommerceMyCoursesCertificationFinding[] */
    public function get_findings(): array {
        return $this->findings;
    }

    public function count(string $severity): int {
        return count(array_filter(
            $this->findings,
            static fn(CommerceMyCoursesCertificationFinding $finding): bool =>
                $finding->get_severity() === $severity
        ));
    }

    public function is_certified(bool $strict = false): bool {
        if ($this->count(CommerceMyCoursesCertificationFinding::ERROR) > 0) {
            return false;
        }
        return !$strict || $this->count(CommerceMyCoursesCertificationFinding::WARNING) === 0;
    }

    /** @return array<string,mixed> */
    public function export(bool $strict = false): array {
        return [
            'generatedat' => time(),
            'certified' => $this->is_certified($strict),
            'strict' => $strict,
            'summary' => [
                'ok' => $this->count(CommerceMyCoursesCertificationFinding::OK),
                'warnings' => $this->count(CommerceMyCoursesCertificationFinding::WARNING),
                'errors' => $this->count(CommerceMyCoursesCertificationFinding::ERROR),
            ],
            'findings' => array_map(
                static fn(CommerceMyCoursesCertificationFinding $finding): array => $finding->export(),
                $this->findings
            ),
        ];
    }
}
