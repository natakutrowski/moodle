<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

final class CommerceRuntimeWriteFinding {
    public function __construct(
        private readonly string $file,
        private readonly int $line,
        private readonly string $operation,
        private readonly string $classification,
        private readonly ?string $table = null,
        private readonly string $reason = ''
    ) {
    }

    public function get_file(): string {
        return $this->file;
    }

    public function get_line(): int {
        return $this->line;
    }

    public function get_operation(): string {
        return $this->operation;
    }

    public function get_classification(): string {
        return $this->classification;
    }

    public function get_table(): ?string {
        return $this->table;
    }

    public function get_reason(): string {
        return $this->reason;
    }

    public function is_direct_legacy_runtime_write(): bool {
        return $this->classification === CommerceRuntimeWriteInventory::CLASS_MIGRATION_CANDIDATE;
    }
}
