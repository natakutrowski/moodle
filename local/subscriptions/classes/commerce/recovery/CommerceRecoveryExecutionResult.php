<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\recovery;

defined('MOODLE_INTERNAL') || die();

/** Result of an idempotent checkout recovery execution. */
final class CommerceRecoveryExecutionResult {
    public function __construct(
        private readonly CommerceRecoveryDiagnostic $before,
        private readonly CommerceRecoveryDiagnostic $after,
        private readonly array $executed,
        private readonly bool $replayed
    ) {
    }

    public function get_before(): CommerceRecoveryDiagnostic { return $this->before; }
    public function get_after(): CommerceRecoveryDiagnostic { return $this->after; }
    public function get_executed_actions(): array { return $this->executed; }
    public function was_replayed(): bool { return $this->replayed; }

    public function to_array(): array {
        return [
            'before' => $this->before->to_array(),
            'after' => $this->after->to_array(),
            'executed_actions' => $this->executed,
            'replayed' => $this->replayed,
        ];
    }
}
