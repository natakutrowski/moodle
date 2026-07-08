<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

abstract class AbstractAutomationAction implements AutomationActionInterface {

    protected function payload_string(AutomationAction $action, string $key, string $default = ''): string {
        return trim((string)($action->payload[$key] ?? $default));
    }

    protected function payload_int(AutomationAction $action, string $key, int $default = 0): int {
        return (int)($action->payload[$key] ?? $default);
    }

    protected function missing_payload(string $key): AutomationActionResult {
        return AutomationActionResult::failure('Missing automation action payload: ' . $key);
    }
}