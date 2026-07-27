<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'batch-size' => 100,
    'strict' => false,
    'verbose' => false,
], [
    'h' => 'help',
    'f' => 'family',
    's' => 'strict',
    'v' => 'verbose',
]);
if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "Audit Legacy/native Commerce backfill.\n\n--family=all|subscription|digital --batch-size=100 --strict --verbose\n";
    exit(0);
}

global $DB;
$registry = CommerceLegacyMigrationFactory::create_source_registry();
$repository = CommercePurchaseSqlRepositoryFactory::create();
$factory = new CommerceLegacySnapshotFactory();
$comparator = new CommerceLegacyNativeComparator();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$verbose = !empty($options['verbose']);
$strict = !empty($options['strict']);
$errors = 0;

cli_heading('Native Commerce backfill audit');
foreach ($families as $family) {
    $source = $registry->get($family);
    $legacytotal = $source->count();
    $nativetotal = $DB->count_records(CommercePersistenceSchema::TABLE_PURCHASE, ['legacyfamily' => $family]);
    $missing = 0;
    $different = 0;
    $invalid = 0;
    $afterid = 0;

    do {
        $ids = $source->get_ids($afterid, $batchsize);
        if ($ids === []) {
            break;
        }
        foreach ($ids as $id) {
            try {
                $purchase = $source->get_by_id($id);
                if ($purchase === null) {
                    $invalid++;
                    continue;
                }
                $expected = $factory->create($purchase);
                $actual = $repository->find_by_legacy_reference($family, $id);
                $comparison = $comparator->compare($expected, $actual);
                if ($comparison->get_status() === 'missing_native') {
                    $missing++;
                } elseif (!$comparison->is_equal()) {
                    $different++;
                }
                if ($verbose && !$comparison->is_equal()) {
                    cli_writeln($family . ':' . $id . ' => ' . $comparison->get_status());
                }
            } catch (\Throwable $exception) {
                $invalid++;
                if ($verbose) {
                    cli_writeln($family . ':' . $id . ' => INVALID: ' . $exception->getMessage());
                }
            }
        }
        $afterid = max($ids);
    } while (true);

    $unexpected = max(0, $nativetotal - ($legacytotal - $missing));
    cli_writeln('');
    cli_writeln('Family: ' . $family);
    cli_writeln('  Legacy total          ' . $legacytotal);
    cli_writeln('  Native total          ' . $nativetotal);
    cli_writeln('  Missing native        ' . $missing);
    cli_writeln('  Different snapshots   ' . $different);
    cli_writeln('  Invalid Legacy        ' . $invalid);
    cli_writeln('  Unexpected native     ' . $unexpected);
    $errors += $missing + $different + $invalid + $unexpected;
}

$orphanitems = $DB->count_records_sql('SELECT COUNT(1) FROM {' . CommercePersistenceSchema::TABLE_ITEM . '} i LEFT JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p ON p.id = i.purchaseid WHERE p.id IS NULL');
$orphanpayments = $DB->count_records_sql('SELECT COUNT(1) FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} x LEFT JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p ON p.id = x.purchaseid WHERE p.id IS NULL');
$orphanfulfillments = $DB->count_records_sql('SELECT COUNT(1) FROM {' . CommercePersistenceSchema::TABLE_FULFILLMENT . '} f LEFT JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p ON p.id = f.purchaseid WHERE p.id IS NULL');
cli_writeln('');
cli_writeln('Orphan items            ' . $orphanitems);
cli_writeln('Orphan payments         ' . $orphanpayments);
cli_writeln('Orphan fulfillments     ' . $orphanfulfillments);
$errors += $orphanitems + $orphanpayments + $orphanfulfillments;

if ($errors > 0) {
    cli_writeln('[ERROR] Native Commerce backfill is incomplete or inconsistent.');
    exit($strict ? 1 : 0);
}
cli_writeln('[OK] Native Commerce backfill is complete and consistent.');
exit(0);