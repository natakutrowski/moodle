<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogLegacyInventoryReport {
    public function __construct(private readonly array $counts, private readonly array $issues) {
    }
    public function get_counts(): array { return $this->counts; }
    public function get_issues(): array { return $this->issues; }
    public function is_healthy(): bool { return empty(array_filter($this->issues, static fn(array $issue): bool => ($issue['severity'] ?? '') === 'error')); }
    public function to_array(): array { return ['counts' => $this->counts, 'issues' => $this->issues, 'healthy' => $this->is_healthy()]; }
}
