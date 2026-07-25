<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\reconciliation\dto;

defined('MOODLE_INTERNAL') || die();

final class CommerceReconciliationIssue {
    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $code,
        private readonly string $severity,
        private readonly string $message
    ) {
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_code(): string { return $this->code; }
    public function get_severity(): string { return $this->severity; }
    public function get_message(): string { return $this->message; }
}
