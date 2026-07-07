<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

abstract class AbstractCommandAction implements CommandActionInterface {

    protected function int_payload(array $payload, string $key): int {
        return (int)($payload[$key] ?? 0);
    }

    protected function string_payload(array $payload, string $key): string {
        return trim((string)($payload[$key] ?? ''));
    }

    protected function missing(string $label): CommandActionResult {
        return CommandActionResult::error($label);
    }
}