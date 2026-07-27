<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\automation\AutomationFactory;
use local_subscriptions\crm\automation\AutomationDispatcher;
use local_subscriptions\crm\automation\AutomationTriggerKeys;

$userid = (int)($argv[1] ?? 0);

if ($userid <= 0) {
    cli_error('Usage: php local/subscriptions/cli/crm/automation/test_automation.php <userid>');
}

$context = AutomationContext::for_user(
    AutomationTriggerKeys::TRIAL_EXPIRED,
    $userid,
    ['source' => 'manual_cli_test']
);

$dispatcher = new AutomationDispatcher();

$results = $dispatcher->dispatch_user(
    AutomationTriggerKeys::TRIAL_EXPIRED,
    $userid,
    ['source' => 'manual_cli_test']
);

cli_writeln('Automation results: ' . count($results));

foreach ($results as $result) {
    cli_writeln('- ' . $result->rule->key . ' / success=' . ($result->success ? 'yes' : 'no') . ' / ' . $result->message);
}