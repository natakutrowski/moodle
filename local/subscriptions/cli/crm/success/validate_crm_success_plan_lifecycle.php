<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanOperationsRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRelationRepository;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanLifecycleService;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanWorkItemService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
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
Validate Customer Success plan lifecycle and integrations.

Options:
--strict       Fail on warnings.
-h, --help     Display this help.

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$tables = [
    'local_subscriptions_cs_plan',
    'local_subscriptions_cs_step',
    'local_subscriptions_cs_relation',
];

foreach ($tables as $tablename) {
    if (
        !$DB->get_manager()->table_exists(
            new xmldb_table($tablename)
        )
    ) {
        $errors[] =
            'Missing table: ' . $tablename;
    } else {
        echo '[OK] Table: ' .
            $tablename .
            PHP_EOL;
    }
}

$classes = [
    CustomerSuccessPlanLifecycleService::class,
    CustomerSuccessPlanRelationRepository::class,
    CustomerSuccessPlanOperationsRepository::class,
    CustomerSuccessPlanWorkItemService::class,
    CustomerSuccessPlanRelation::class,
];

foreach ($classes as $class) {
    if (!class_exists($class)) {
        $errors[] =
            'Missing class: ' . $class;
    } else {
        echo '[OK] Class: ' .
            $class .
            PHP_EOL;
    }
}

$metrics =
    (new CustomerSuccessPlanOperationsRepository())
        ->get_dashboard_metrics();

foreach (
    [
        'openplans',
        'activeplans',
        'blockedsteps',
        'completedtoday',
        'criticalopen',
        'averageprogress',
    ]
    as $key
) {
    if (!array_key_exists($key, $metrics)) {
        $errors[] =
            'Missing dashboard metric: ' . $key;
    }
}

if ($errors === []) {
    echo '[OK] Dashboard metrics validated.' .
        PHP_EOL;
}

foreach ($warnings as $warning) {
    echo '[WARNING] ' .
        $warning .
        PHP_EOL;
}

foreach ($errors as $error) {
    echo '[ERROR] ' .
        $error .
        PHP_EOL;
}

if (
    $errors !== [] ||
    (
        !empty($options['strict']) &&
        $warnings !== []
    )
) {
    exit(1);
}

echo '[OK] Customer Success plan lifecycle validated.' .
    PHP_EOL;

exit(0);