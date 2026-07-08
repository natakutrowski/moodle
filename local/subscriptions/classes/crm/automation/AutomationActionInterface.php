<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

interface AutomationActionInterface {

    public function key(): string;

    public function execute(AutomationAction $action, AutomationContext $context): AutomationActionResult;
}