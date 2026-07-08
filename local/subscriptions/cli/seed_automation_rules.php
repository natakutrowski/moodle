<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\automation\AutomationRuleSeeder;

$seeder = new AutomationRuleSeeder();
$count = $seeder->seed_defaults();

cli_writeln('CRM automation rules seeded: ' . $count);