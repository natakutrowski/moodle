<?php

namespace local_subscriptions\commerce\audit\purchase;

defined('MOODLE_INTERNAL') || die();

/**
 * Report for the Commerce Purchase Architecture audit.
 */
final class CommercePurchaseArchitectureAuditReport {

    private array $counters = [];

    private array $warnings = [];

    private array $errors = [];

    public function increment(
        string $key,
        int $amount = 1
    ): void {
        $this->counters[$key] =
            ($this->counters[$key] ?? 0)
            + $amount;
    }

    public function add_warning(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->warnings[] = [
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function add_error(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->errors[] = [
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function get_counters(): array {
        return $this->counters;
    }

    public function get_warnings(): array {
        return $this->warnings;
    }

    public function get_errors(): array {
        return $this->errors;
    }

    public function has_errors(): bool {
        return $this->errors !== [];
    }

    public function has_warnings(): bool {
        return $this->warnings !== [];
    }

    public function to_array(): array {
        return [
            'counters' => $this->counters,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
        ];
    }
}