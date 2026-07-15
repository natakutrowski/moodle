<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxRetentionRepository;
use local_subscriptions\crm\inbox\services\InboxRetentionService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'account' => 0,
        'closed-days' => null,
        'deleted-days' => null,
        'log-days' => null,
        'limit' => 100,
    ],
    [
        'h' => 'help',
        'x' => 'execute',
        'a' => 'account',
        'l' => 'limit',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if ($options['help']) {
    echo <<<HELP
Clean up expired CRM Inbox data.

By default, the command runs in simulation mode and deletes nothing.

Options:
--account=ID          Inbox account identifier
--closed-days=730     Retention period for closed conversations
--deleted-days=30     Retention period for locally deleted conversations
--log-days=90         Retention period for synchronisation logs
--limit=100           Maximum number of conversations per execution
--execute             Perform the deletion
-h, --help            Display this help

Examples:

Simulation:
php local/subscriptions/cli/cleanup_crm_inbox.php

Real cleanup:
php local/subscriptions/cli/cleanup_crm_inbox.php --execute

HELP;

    exit(0);
}

$accounts = new InboxAccountRepository();

$account = !empty($options['account'])
    ? $accounts->find((int)$options['account'])
    : $accounts->find_by_email(
        'support@campusfr.fr'
    );

if (!$account) {
    throw new moodle_exception(
        'crm_inbox_account_not_found',
        'local_subscriptions'
    );
}

$retention = $account->configuration['retention']
    ?? [];

if (!is_array($retention)) {
    $retention = [];
}

$closeddays = $options['closed-days'] !== null
    ? max(1, (int)$options['closed-days'])
    : max(
        1,
        (int)($retention['closed_days'] ?? 730)
    );

$deletedays = $options['deleted-days'] !== null
    ? max(1, (int)$options['deleted-days'])
    : max(
        1,
        (int)(
            $retention['locally_deleted_days']
            ?? 30
        )
    );

$logdays = $options['log-days'] !== null
    ? max(1, (int)$options['log-days'])
    : max(
        1,
        (int)($retention['sync_logs_days'] ?? 90)
    );

$limit = max(
    1,
    min(
        1000,
        (int)$options['limit']
    )
);

$execute = !empty($options['execute']);

$service = new InboxRetentionService(
    new InboxRetentionRepository()
);

$result = $service->cleanup(
    $closeddays,
    $deletedays,
    $logdays,
    $limit,
    !$execute
);

mtrace('CRM Inbox cleanup');
mtrace('=================');
mtrace('Account: ' . $account->email);
mtrace('Mode: ' . (
    $result['dryrun']
        ? 'simulation'
        : 'execution'
));
mtrace('Closed retention: ' . $closeddays . ' day(s)');
mtrace('Deleted retention: ' . $deletedays . ' day(s)');
mtrace('Sync log retention: ' . $logdays . ' day(s)');
mtrace('Threads selected: ' . $result['threadcount']);

if (!empty($result['threadids'])) {
    mtrace(
        'Thread IDs: ' .
        implode(', ', $result['threadids'])
    );
}

if (!$result['dryrun']) {
    mtrace(
        'Deleted sync logs: ' .
        $result['deletedlogs']
    );
}

if ($result['dryrun']) {
    mtrace('');
    mtrace(
        'Simulation only. Run again with --execute to delete these records.'
    );
}