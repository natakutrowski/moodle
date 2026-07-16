<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\services\WorkItemService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'create-test' => false,
        'userid' => 0,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Work Management diagnostic.

Options:
--create-test       Create a test Work Item.
--userid=ID         Optional target Moodle user.
-h, --help          Display this help.

HELP;

    exit(0);
}

$requiredtables = [
    'local_subscriptions_work_team',
    'local_subscriptions_work_team_member',
    'local_subscriptions_work_item',
    'local_subscriptions_work_comment',
    'local_subscriptions_work_link',
    'local_subscriptions_work_history',
];

foreach ($requiredtables as $tablename) {
    $exists = $DB->get_manager()->table_exists(
        new xmldb_table($tablename)
    );

    echo $tablename .
        ': ' .
        ($exists ? 'available' : 'missing') .
        PHP_EOL;
}

if (!empty($options['create-test'])) {
    $userid = (int)$options['userid'];

    $service = new WorkItemService();

    $item = $service->create(
        new CreateWorkItemRequest(
            'Diagnostic Work Item',
            'Created by the Work Management diagnostic CLI.',
            WorkItemType::TASK,
            WorkItemPriority::LOW,
            WorkItemSource::SYSTEM,
            (int)get_admin()->id,
            $userid > 0 ? $userid : null
        )
    );

    echo 'Created: ' .
        $item->reference .
        ' (#' .
        $item->id .
        ')' .
        PHP_EOL;
}