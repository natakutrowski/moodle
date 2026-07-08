<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\automation\AutomationRuleService;

$id = (int)($argv[1] ?? 0);
$action = (string)($argv[2] ?? '');

if ($id <= 0 || !in_array($action, ['enable', 'disable'], true)) {
    cli_error('Usage: php local/subscriptions/cli/toggle_automation_rule.php <ruleid> <enable|disable>');
}

$service = new AutomationRuleService();

if ($action === 'enable') {
    $service->enable($id);
    cli_writeln('Automation rule enabled: ' . $id);
} else {
    $service->disable($id);
    cli_writeln('Automation rule disabled: ' . $id);
}