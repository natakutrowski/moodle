<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\automation\AutomationRuleService;

$service = new AutomationRuleService();
$rules = $service->get_all();

cli_writeln('CRM automation rules: ' . count($rules));

foreach ($rules as $rule) {
    cli_writeln(sprintf(
        '#%d [%s] %s | trigger=%s | priority=%d',
        $rule->id,
        $rule->enabled ? 'enabled' : 'disabled',
        $rule->key,
        $rule->trigger->key,
        $rule->priority
    ));
}