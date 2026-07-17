<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRepository;

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
Validate Customer Success plan foundations.

Options:
--strict       Fail on warnings.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/validate_crm_success_plans.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$tables = [
    'local_subscriptions_cs_plan',
    'local_subscriptions_cs_step',
];

foreach ($tables as $tablename) {
    $table = new xmldb_table(
        $tablename
    );

    if (
        !$DB->get_manager()
            ->table_exists($table)
    ) {
        $errors[] =
            'Missing table: ' .
            $tablename;

        continue;
    }

    echo '[OK] Table available: ' .
        $tablename .
        PHP_EOL;
}

$classes = [
    CustomerSuccessPlanStatus::class,
    CustomerSuccessPlanStepStatus::class,
    CustomerSuccessPlanSource::class,
    CustomerSuccessPlanRelationType::class,
    CustomerSuccessPlanRepository::class,
    CustomerSuccessPlanReadRepository::class,
];

foreach ($classes as $class) {
    if (!class_exists($class)) {
        $errors[] =
            'Missing class: ' .
            $class;

        continue;
    }

    echo '[OK] Class available: ' .
        $class .
        PHP_EOL;
}

if (
    count(
        CustomerSuccessPlanStatus::all()
    ) !== 5
) {
    $errors[] =
        'Unexpected Customer Success plan status count.';
} else {
    echo '[OK] Plan statuses validated.' .
        PHP_EOL;
}

if (
    count(
        CustomerSuccessPlanStepStatus::all()
    ) !== 6
) {
    $errors[] =
        'Unexpected Customer Success plan step status count.';
} else {
    echo '[OK] Plan step statuses validated.' .
        PHP_EOL;
}

if (
    CustomerSuccessPlanSource::all() === []
) {
    $errors[] =
        'Customer Success plan sources are empty.';
} else {
    echo '[OK] Plan sources validated.' .
        PHP_EOL;
}

if (
    CustomerSuccessPlanRelationType::all() === []
) {
    $errors[] =
        'Customer Success plan relation types are empty.';
} else {
    echo '[OK] Plan relation types validated.' .
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

echo '[OK] Customer Success plan foundations validated.' .
    PHP_EOL;

exit(0);