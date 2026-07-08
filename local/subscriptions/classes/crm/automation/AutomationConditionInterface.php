<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

interface AutomationConditionInterface {

    public function key(): string;

    public function evaluate(AutomationCondition $condition, AutomationContext $context): bool;
}