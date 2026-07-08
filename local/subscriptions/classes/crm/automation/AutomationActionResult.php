<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationActionResult {

    private function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly array $data = []
    ) {
    }

    public static function success(string $message = '', array $data = []): self {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $data = []): self {
        return new self(false, $message, $data);
    }
}