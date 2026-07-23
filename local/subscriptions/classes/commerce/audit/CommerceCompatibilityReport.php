<?php

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a Commerce compatibility audit.
 */
final class CommerceCompatibilityReport {

    /** @var CommerceCompatibilityIssue[] */
    private array $issues = [];

    /** @var array<string,int> */
    private array $counters = [];

    public function increment(
        string $key,
        int $amount = 1
    ): void {
        if (!array_key_exists($key, $this->counters)) {
            $this->counters[$key] = 0;
        }

        $this->counters[$key] += $amount;
    }

    public function set_counter(
        string $key,
        int $value
    ): void {
        $this->counters[$key] = $value;
    }

    public function add_issue(
        CommerceCompatibilityIssue $issue
    ): void {
        $this->issues[] = $issue;
    }

    public function add_warning(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->add_issue(
            new CommerceCompatibilityIssue(
                CommerceCompatibilityIssue::SEVERITY_WARNING,
                $code,
                $message,
                $context
            )
        );
    }

    public function add_error(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->add_issue(
            new CommerceCompatibilityIssue(
                CommerceCompatibilityIssue::SEVERITY_ERROR,
                $code,
                $message,
                $context
            )
        );
    }

    /**
     * @return array<string,int>
     */
    public function get_counters(): array {
        return $this->counters;
    }

    /**
     * @return CommerceCompatibilityIssue[]
     */
    public function get_issues(): array {
        return $this->issues;
    }

    public function get_issue_count(): int {
        return count($this->issues);
    }

    public function count_by_severity(
        string $severity
    ): int {
        return count(
            array_filter(
                $this->issues,
                static fn(
                    CommerceCompatibilityIssue $issue
                ): bool => $issue->get_severity() === $severity
            )
        );
    }

    public function has_issues(): bool {
        return $this->issues !== [];
    }

    public function has_errors(): bool {
        return $this->count_by_severity(
            CommerceCompatibilityIssue::SEVERITY_ERROR
        ) > 0;
    }

    public function to_array(): array {
        return [
            'counters' => $this->counters,
            'issues' => array_map(
                static fn(
                    CommerceCompatibilityIssue $issue
                ): array => $issue->to_array(),
                $this->issues
            ),
            'summary' => [
                'issues' => $this->get_issue_count(),
                'warnings' => $this->count_by_severity(
                    CommerceCompatibilityIssue::SEVERITY_WARNING
                ),
                'errors' => $this->count_by_severity(
                    CommerceCompatibilityIssue::SEVERITY_ERROR
                ),
            ],
        ];
    }
}