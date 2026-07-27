<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => '',
    'legacy-id' => 0,
    'execute' => false,
    'confirm-native-refresh' => '',
    'report-file' => '',
], [
    'h' => 'help',
    'f' => 'family',
    'i' => 'legacy-id',
    'e' => 'execute',
]);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if ($options['help']) {
    echo <<<HELP
Refresh one existing Native Commerce aggregate from its current Legacy projection.

The default mode is read-only. Execution is deliberately restricted to one
Legacy identifier and requires an exact confirmation token.

Options:
  --family=subscription|digital
  --legacy-id=ID
  -e, --execute
  --confirm-native-refresh=FAMILY:ID
  --report-file=PATH
  -h, --help

Dry-run:
  php local/subscriptions/cli/commerce/migration/refresh_native_purchase.php \
    --family=subscription --legacy-id=1032

Execute after reviewing the dry-run:
  php local/subscriptions/cli/commerce/migration/refresh_native_purchase.php \
    --family=subscription --legacy-id=1032 --execute \
    --confirm-native-refresh=subscription:1032 \
    --report-file=/tmp/campusfr-refresh-subscription-1032.json

Safety policy:
  - never creates a missing Native purchase;
  - refreshes exactly one complete Native aggregate;
  - refuses immutable identity differences;
  - uses the official Legacy snapshot factory and SQL repository;
  - verifies complete Legacy/Native equality before committing;
  - rolls back automatically when verification fails.

HELP;
    exit(0);
}

$family = strtolower(trim((string)$options['family']));
$legacyid = max(0, (int)$options['legacy-id']);
$execute = !empty($options['execute']);
$confirmation = trim((string)$options['confirm-native-refresh']);
$reportfile = trim((string)$options['report-file']);

if (!in_array($family, ['subscription', 'digital'], true)) {
    cli_error('--family must be subscription or digital.');
}
if ($legacyid <= 0) {
    cli_error('--legacy-id must be a positive integer.');
}
if ($execute && $confirmation !== $family . ':' . $legacyid) {
    cli_error('--execute requires --confirm-native-refresh=' . $family . ':' . $legacyid);
}

$sources = CommerceLegacyMigrationFactory::create_source_registry();
$snapshotfactory = new CommerceLegacySnapshotFactory();
$repository = CommercePurchaseSqlRepositoryFactory::create();
$comparator = new CommerceLegacyNativeComparator();

$legacypurchase = $sources->get($family)->get_by_id($legacyid);
if ($legacypurchase === null) {
    cli_error('Legacy purchase not found: ' . $family . ' #' . $legacyid);
}

$expected = $snapshotfactory->create($legacypurchase);
$actual = $repository->find_by_legacy_reference($family, $legacyid);
if ($actual === null) {
    cli_error(
        'Native purchase is missing. Use migrate_legacy_commerce_purchases.php instead; '
        . 'this command never creates missing aggregates.'
    );
}

$assertsameidentity = static function (
    CommercePurchasePersistenceSnapshot $expected,
    CommercePurchasePersistenceSnapshot $actual
): void {
    $expectedpurchase = (array)$expected->get_purchase()->to_record();
    $actualpurchase = (array)$actual->get_purchase()->to_record();

    foreach ([
        'purchaseuuid',
        'reference',
        'type',
        'legacyfamily',
        'legacyid',
        'timecreated',
    ] as $field) {
        if (($expectedpurchase[$field] ?? null) !== ($actualpurchase[$field] ?? null)) {
            throw new \RuntimeException(
                'Refresh refused because immutable identity differs: ' . $field
            );
        }
    }
};

$assertsameidentity($expected, $actual);
$comparison = $comparator->compare($expected, $actual);

$report = [
    'format_version' => 1,
    'mode' => $execute ? 'execute' : 'dry_run',
    'started_at' => time(),
    'family' => $family,
    'legacyid' => $legacyid,
    'status_before' => $comparison->get_status(),
    'differences_before' => $comparison->get_differences(),
    'updated_native_purchase_id' => null,
    'status_after' => null,
    'differences_after' => null,
    'result' => 'inspected',
];

cli_heading('CampusFR Commerce - Complete Native Aggregate Refresh');
cli_writeln('Target: ' . $family . ' #' . $legacyid);
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));
cli_writeln('Current comparison: ' . $comparison->get_status());

if ($comparison->is_equal()) {
    cli_writeln('[OK] Native aggregate is already equal to the current Legacy projection.');
    $report['result'] = 'already_equal';
} else {
    cli_writeln('Differences:');
    foreach ($comparison->get_differences() as $section => $difference) {
        cli_writeln('  - ' . $section);
        cli_writeln(
            '    expected: ' . json_encode(
                $difference['expected'] ?? null,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
        cli_writeln(
            '    actual:   ' . json_encode(
                $difference['actual'] ?? null,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }

    if (!$execute) {
        cli_writeln('[OK] Dry-run complete. Review the complete diff before using --execute.');
        $report['result'] = 'dry_run_complete';
    } else {
        $transaction = $DB->start_delegated_transaction();
        try {
            // Re-read inside the transaction so the identity check covers the
            // exact aggregate that is about to be replaced.
            $current = $repository->find_by_legacy_reference($family, $legacyid);
            if ($current === null) {
                throw new \RuntimeException('Native purchase disappeared before refresh.');
            }
            $assertsameidentity($expected, $current);

            $report['updated_native_purchase_id'] = $repository->save($expected);

            $persisted = $repository->find_by_legacy_reference($family, $legacyid);
            $verification = $comparator->compare($expected, $persisted);
            $report['status_after'] = $verification->get_status();
            $report['differences_after'] = $verification->get_differences();

            if (!$verification->is_equal()) {
                throw new \RuntimeException(
                    'Post-refresh Legacy/Native verification failed.'
                );
            }

            $transaction->allow_commit();
            $report['result'] = 'refreshed_and_verified';
            cli_writeln('[OK] Complete Native aggregate refreshed, verified and committed.');
        } catch (\Throwable $exception) {
            $report['result'] = 'rolled_back';
            $report['error'] = $exception->getMessage();

            try {
                $transaction->rollback($exception);
            } catch (\Throwable $rolledback) {
                $report['error'] = $rolledback->getMessage();
            }

            cli_writeln('[ERROR] Refresh rolled back: ' . $report['error']);
            $report['finished_at'] = time();

            if ($reportfile !== '') {
                $directory = dirname($reportfile);
                if (is_dir($directory) && is_writable($directory)) {
                    $temporary = $reportfile . '.tmp.' . getmypid();
                    $json = json_encode(
                        $report,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                    ) . PHP_EOL;
                    if (file_put_contents($temporary, $json, LOCK_EX) !== false) {
                        @rename($temporary, $reportfile);
                    }
                }
            }

            exit(1);
        }
    }
}

$report['finished_at'] = time();
if ($reportfile !== '') {
    $directory = dirname($reportfile);
    if (!is_dir($directory) || !is_writable($directory)) {
        cli_error('Report directory is not writable: ' . $directory);
    }

    $temporary = $reportfile . '.tmp.' . getmypid();
    $json = json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;

    if (file_put_contents($temporary, $json, LOCK_EX) === false ||
            !rename($temporary, $reportfile)) {
        @unlink($temporary);
        cli_error('Unable to write report atomically: ' . $reportfile);
    }

    cli_writeln('Report: ' . $reportfile);
}

exit(0);