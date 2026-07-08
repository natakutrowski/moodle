<?php

namespace local_subscriptions\crm\automation\conditions;

use local_subscriptions\crm\automation\AbstractAutomationCondition;
use local_subscriptions\crm\automation\AutomationCondition;
use local_subscriptions\crm\automation\AutomationConditionKeys;
use local_subscriptions\crm\automation\AutomationContext;

defined('MOODLE_INTERNAL') || die();

final class EventNoteTypeIsCondition extends AbstractAutomationCondition {

    public function key(): string {
        return AutomationConditionKeys::EVENT_NOTE_TYPE_IS;
    }

    public function evaluate(AutomationCondition $condition, AutomationContext $context): bool {
        $expected = $this->payload_string($condition, 'type');
        $actual = trim((string)$context->get('type', ''));

        return $this->apply_negation($condition, $expected !== '' && $actual === $expected);
    }
}