<?php

namespace local_subscriptions\commerce\audit\runtime;

defined('MOODLE_INTERNAL') || die();

/** Read-only report for post-payment Commerce consistency. */
final class CommerceRuntimeAuditReport {
    public function __construct(
        private readonly array $checks,
        private readonly array $issues
    ) {
    }

    public function get_checks(): array {
        return $this->checks;
    }

    public function get_issues(): array {
        return $this->issues;
    }

    public function has_errors(): bool {
        return array_filter($this->issues, static fn(array $issue): bool => ($issue['severity'] ?? '') === 'error') !== [];
    }

    public function has_warnings(): bool {
        return array_filter($this->issues, static fn(array $issue): bool => ($issue['severity'] ?? '') === 'warning') !== [];
    }

    public function get_status(): string {
        if ($this->has_errors()) {
            return 'BLOCKED';
        }
        if ($this->has_warnings()) {
            return 'READY_WITH_WARNINGS';
        }
        return 'READY';
    }

    public function to_array(): array {
        return [
            'status' => $this->get_status(),
            'checks' => $this->checks,
            'issues' => $this->issues,
        ];
    }
}
