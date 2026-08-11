<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Aggregated final certification report for the complete customer journey. */
final class CommerceCustomerJourneyCertificationReport {
    /** @var CommerceCustomerJourneyCertificationFinding[] */
    private array $findings = [];

    public function add(CommerceCustomerJourneyCertificationFinding $finding): void {
        $this->findings[] = $finding;
    }

    /** @return CommerceCustomerJourneyCertificationFinding[] */
    public function get_findings(): array { return $this->findings; }

    public function count(string $severity): int {
        return count(array_filter(
            $this->findings,
            static fn(CommerceCustomerJourneyCertificationFinding $finding): bool =>
                $finding->get_severity() === $severity
        ));
    }

    public function is_certified(bool $strict = false): bool {
        if ($this->count(CommerceCustomerJourneyCertificationFinding::ERROR) > 0) {
            return false;
        }
        return !$strict || $this->count(CommerceCustomerJourneyCertificationFinding::WARNING) === 0;
    }

    /** @return array<string,mixed> */
    public function export(bool $strict = false): array {
        return [
            'generatedat' => time(),
            'certified' => $this->is_certified($strict),
            'strict' => $strict,
            'summary' => [
                'ok' => $this->count(CommerceCustomerJourneyCertificationFinding::OK),
                'warnings' => $this->count(CommerceCustomerJourneyCertificationFinding::WARNING),
                'errors' => $this->count(CommerceCustomerJourneyCertificationFinding::ERROR),
            ],
            'findings' => array_map(
                static fn(CommerceCustomerJourneyCertificationFinding $finding): array => $finding->export(),
                $this->findings
            ),
        ];
    }
}
