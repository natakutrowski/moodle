<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\reconciliation\dto;

defined('MOODLE_INTERNAL') || die();

final class CommerceReconciliationResult {
    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly array $issues,
        private readonly bool $repaired = false
    ) {
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_issues(): array { return $this->issues; }
    public function was_repaired(): bool { return $this->repaired; }
    public function is_equal(): bool { return $this->issues === []; }
}
