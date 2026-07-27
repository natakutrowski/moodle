<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
    ]
);

if ($unrecognised !== []) {
    $unrecognised = implode("\n  ", $unrecognised);

    cli_error(
        "Unknown options:\n  {$unrecognised}"
    );
}

if ($options['help']) {
    echo <<<HELP
Audit native Commerce persistence.

Options:
-h, --help       Display this help.
-s, --strict     Exit with an error when a check fails.

Example:
php local/subscriptions/cli/commerce/audit/audit_commerce_native_persistence.php --strict

HELP;

    exit(0);
}

global $DB;

$checks = [];
$errors = [];

$tables = [
    CommercePersistenceSchema::TABLE_PURCHASE,
    CommercePersistenceSchema::TABLE_ITEM,
    CommercePersistenceSchema::TABLE_PAYMENT,
    CommercePersistenceSchema::TABLE_FULFILLMENT,
];

$dbman = $DB->get_manager();

foreach ($tables as $tablename) {
    $exists = $dbman->table_exists(
        new xmldb_table($tablename)
    );

    $checks["table:{$tablename}"] =
        $exists ? 'OK' : 'MISSING';

    if (!$exists) {
        $errors[] = "Missing table: {$tablename}";
    }
}

try {
    $repository =
        CommercePurchaseSqlRepositoryFactory::create();

    $checks['repository_factory'] =
        $repository instanceof
            \local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository
            ? 'OK'
            : 'INVALID';
} catch (\Throwable $exception) {
    $checks['repository_factory'] = 'ERROR';
    $errors[] = $exception::class
        . ': '
        . $exception->getMessage();
}

if ($errors === []) {
    try {
        $missing = $repository->find_by_uuid(
            md5('commerce-native-persistence-audit-missing')
        );

        $checks['unknown_purchase_returns_null'] =
            $missing === null ? 'OK' : 'INVALID';

        if ($missing !== null) {
            $errors[] =
                'Unknown Commerce purchase did not return null.';
        }
    } catch (\Throwable $exception) {
        $checks['unknown_purchase_returns_null'] = 'ERROR';
        $errors[] = $exception::class
            . ': '
            . $exception->getMessage();
    }
}

cli_heading('Native Commerce persistence audit');

foreach ($checks as $check => $result) {
    cli_writeln(
        str_pad($check, 50)
            . ' '
            . $result
    );
}

cli_writeln('');

$counts = [
    'purchases' => $DB->count_records(
        CommercePersistenceSchema::TABLE_PURCHASE
    ),
    'items' => $DB->count_records(
        CommercePersistenceSchema::TABLE_ITEM
    ),
    'payments' => $DB->count_records(
        CommercePersistenceSchema::TABLE_PAYMENT
    ),
    'fulfillments' => $DB->count_records(
        CommercePersistenceSchema::TABLE_FULFILLMENT
    ),
];

foreach ($counts as $label => $count) {
    cli_writeln(
        str_pad($label, 50)
            . ' '
            . $count
    );
}

cli_writeln('');

if ($errors !== []) {
    cli_writeln('[ERROR] Native Commerce persistence audit failed.');

    foreach ($errors as $error) {
        cli_writeln(' - ' . $error);
    }

    if ($options['strict']) {
        exit(1);
    }

    exit(0);
}

cli_writeln('[OK] Native Commerce persistence boundary is valid.');

exit(0);