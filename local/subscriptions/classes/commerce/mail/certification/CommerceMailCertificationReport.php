<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\certification;

defined('MOODLE_INTERNAL') || die();

/** Aggregated, read-only certification report. */
final class CommerceMailCertificationReport {

    /** @var CommerceMailCertificationFinding[] */
    private array $findings = [];

    public function add(CommerceMailCertificationFinding $finding): void {
        $this->findings[] = $finding;
    }

    /** @return CommerceMailCertificationFinding[] */
    public function get_findings(): array {
        return $this->findings;
    }

    public function count(string $severity): int {
        return count(array_filter(
            $this->findings,
            static fn(CommerceMailCertificationFinding $finding): bool => $finding->get_severity() === $severity
        ));
    }

    public function has_errors(): bool {
        return $this->count(CommerceMailCertificationFinding::ERROR) > 0;
    }

    public function has_warnings(): bool {
        return $this->count(CommerceMailCertificationFinding::WARNING) > 0;
    }

    public function is_certified(bool $strict = false): bool {
        if ($this->has_errors()) {
            return false;
        }
        return !$strict || !$this->has_warnings();
    }

    /** @return array{certified:bool,strict:bool,summary:array<string,int>,findings:array<int,array<string,string>>} */
    public function export(bool $strict = false): array {
        return [
            'certified' => $this->is_certified($strict),
            'strict' => $strict,
            'summary' => [
                'ok' => $this->count(CommerceMailCertificationFinding::OK),
                'warnings' => $this->count(CommerceMailCertificationFinding::WARNING),
                'errors' => $this->count(CommerceMailCertificationFinding::ERROR),
            ],
            'findings' => array_map(
                static fn(CommerceMailCertificationFinding $finding): array => $finding->export(),
                $this->findings
            ),
        ];
    }
}
