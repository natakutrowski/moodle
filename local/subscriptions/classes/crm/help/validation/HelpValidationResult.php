<?php

namespace local_subscriptions\crm\help\validation;

defined('MOODLE_INTERNAL') || die();

final class HelpValidationResult {

    private array $errors = [];
    private array $warnings = [];
    private array $successes = [];

    public function add_error(string $message): void {
        $this->errors[] = $message;
    }

    public function add_warning(string $message): void {
        $this->warnings[] = $message;
    }

    public function add_success(string $message): void {
        $this->successes[] = $message;
    }

    public function errors(): array {
        return $this->errors;
    }

    public function warnings(): array {
        return $this->warnings;
    }

    public function successes(): array {
        return $this->successes;
    }

    public function has_errors(): bool {
        return $this->errors !== [];
    }

    public function is_valid(): bool {
        return !$this->has_errors();
    }

    public function error_count(): int {
        return count($this->errors);
    }

    public function warning_count(): int {
        return count($this->warnings);
    }

    public function success_count(): int {
        return count($this->successes);
    }
}