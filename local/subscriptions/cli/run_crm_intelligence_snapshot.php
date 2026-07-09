<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'limit' => \local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits::SNAPSHOT_USERS,
], [
    'h' => 'help',
    'l' => 'limit',
]);

if (!empty($options['help'])) {
    echo "Create CRM intelligence score snapshots.\n\n";
    echo "Options:\n";
    echo "--limit=50    Number of users to analyse.\n";
    exit(0);
}

$runner = new \local_subscriptions\crm\intelligence\history\CrmScoreSnapshotRunner();
$count = $runner->run((int)$options['limit']);

mtrace('CRM intelligence snapshots created: ' . $count);