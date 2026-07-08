<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

abstract class AbstractAutomationCondition implements AutomationConditionInterface {

    protected function payload_string(AutomationCondition $condition, string $key, string $default = ''): string {
        return trim((string)($condition->payload[$key] ?? $default));
    }

    protected function payload_int(AutomationCondition $condition, string $key, int $default = 0): int {
        return (int)($condition->payload[$key] ?? $default);
    }

    protected function payload_float(AutomationCondition $condition, string $key, float $default = 0.0): float {
        return (float)($condition->payload[$key] ?? $default);
    }

    protected function apply_negation(AutomationCondition $condition, bool $result): bool {
        return $condition->negated ? !$result : $result;
    }
}