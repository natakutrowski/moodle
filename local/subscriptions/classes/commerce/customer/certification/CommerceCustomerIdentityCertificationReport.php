<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

/** Aggregated read-only certification report for Commerce customer identity integrity. */
final class CommerceCustomerIdentityCertificationReport {
    /** @var CommerceCustomerIdentityCertificationFinding[] */
    private array $findings = [];

    /** @var array<string,int> */
    private array $metrics = [];

    public function add(CommerceCustomerIdentityCertificationFinding $finding): void {
        $this->findings[] = $finding;
    }

    public function set_metric(string $key, int $value): void {
        $this->metrics[$key] = $value;
    }

    /** @return CommerceCustomerIdentityCertificationFinding[] */
    public function get_findings(): array {
        return $this->findings;
    }

    /** @return array<string,int> */
    public function get_metrics(): array {
        return $this->metrics;
    }

    public function count(string $severity): int {
        return count(array_filter(
            $this->findings,
            static fn(CommerceCustomerIdentityCertificationFinding $finding): bool => $finding->severity === $severity
        ));
    }

    public function is_certified(bool $strict = false): bool {
        if ($this->count(CommerceCustomerIdentityCertificationFinding::ERROR) > 0) {
            return false;
        }
        return !$strict || $this->count(CommerceCustomerIdentityCertificationFinding::WARNING) === 0;
    }

    /** @return array<string,mixed> */
    public function export(bool $strict = false): array {
        return [
            'generatedat' => time(),
            'certified' => $this->is_certified($strict),
            'strict' => $strict,
            'summary' => [
                'ok' => $this->count(CommerceCustomerIdentityCertificationFinding::OK),
                'warnings' => $this->count(CommerceCustomerIdentityCertificationFinding::WARNING),
                'errors' => $this->count(CommerceCustomerIdentityCertificationFinding::ERROR),
            ],
            'metrics' => $this->metrics,
            'findings' => array_map(
                static fn(CommerceCustomerIdentityCertificationFinding $finding): array => $finding->export(),
                $this->findings
            ),
        ];
    }
}
