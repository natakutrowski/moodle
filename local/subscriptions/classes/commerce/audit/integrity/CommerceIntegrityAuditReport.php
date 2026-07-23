<?php

namespace local_subscriptions\commerce\audit\integrity;

defined('MOODLE_INTERNAL') || die();

/** Read-only Commerce integrity report. */
final class CommerceIntegrityAuditReport {
    public function __construct(private readonly array $metrics, private readonly array $issues) {}
    public function metrics(): array { return $this->metrics; }
    public function issues(): array { return $this->issues; }
    public function has_errors(): bool {
        return array_filter($this->issues, static fn(array $i): bool => ($i['severity'] ?? '') === 'error') !== [];
    }
    public function has_warnings(): bool {
        return array_filter($this->issues, static fn(array $i): bool => ($i['severity'] ?? '') === 'warning') !== [];
    }
    public function status(): string {
        return $this->has_errors() ? 'BLOCKED' : ($this->has_warnings() ? 'READY_WITH_WARNINGS' : 'READY');
    }
    public function to_array(): array { return ['status' => $this->status(), 'metrics' => $this->metrics, 'issues' => $this->issues]; }
}
