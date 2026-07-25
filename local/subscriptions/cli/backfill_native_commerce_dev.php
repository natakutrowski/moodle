<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'id' => 0,
    'after-id' => 0,
    'limit' => 0,
    'batch-size' => 100,
    'execute' => false,
    'confirm-dev-backfill' => false,
    'strict' => false,
    'verbose' => false,
    'report-file' => '',
], [
    'h' => 'help',
    'f' => 'family',
    'i' => 'id',
    'e' => 'execute',
    's' => 'strict',
    'v' => 'verbose',
]);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo <<<HELP
I4D.9 DEV native Commerce backfill.

Dry-run is the default. Writing requires BOTH:
  --execute --confirm-dev-backfill

This command is intentionally a DEV validation tool. A separate hardened,
resumable and throttled production backfill will be delivered after 7.93J.

Options:
  --family=all|subscription|digital
  --id=ID
  --after-id=ID
  --limit=N
  --batch-size=N
  --execute
  --confirm-dev-backfill
  --strict
  --verbose
  --report-file=/absolute/path/report.json
  --help

HELP;
    exit(0);
}

$execute = !empty($options['execute']);
if ($execute && empty($options['confirm-dev-backfill'])) {
    cli_error('Writing requires --execute --confirm-dev-backfill.');
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$migrator = CommerceLegacyMigrationFactory::create_migrator();
$repository = CommercePurchaseSqlRepositoryFactory::create();
$snapshotfactory = new CommerceLegacySnapshotFactory();
$comparator = new CommerceLegacyNativeComparator();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
$singleid = max(0, (int)$options['id']);
$afteridoption = max(0, (int)$options['after-id']);
$limit = max(0, (int)$options['limit']);
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$strict = !empty($options['strict']);
$verbose = !empty($options['verbose']);
$reportfile = trim((string)$options['report-file']);
$started = microtime(true);
$overallfailed = false;
$report = [
    'phase' => '7.93I4D.9',
    'environment' => 'DEV',
    'mode' => $execute ? 'execute' : 'dry_run',
    'startedat' => time(),
    'families' => [],
];

cli_heading('I4D.9 DEV native Commerce backfill');
cli_writeln('Mode: ' . strtoupper($report['mode']));

foreach ($families as $family) {
    $source = $registry->get($family);
    $processed = 0;
    $afterid = $afteridoption;
    $counters = [];
    $results = [];

    cli_writeln('');
    cli_writeln('Family: ' . $family . ' (Legacy total: ' . $source->count() . ')');

    do {
        if ($singleid > 0) {
            $ids = [$singleid];
        } else {
            $remaining = $limit > 0 ? $limit - $processed : $batchsize;
            if ($limit > 0 && $remaining <= 0) {
                break;
            }
            $ids = $source->get_ids($afterid, min($batchsize, $remaining));
        }
        if ($ids === []) {
            break;
        }

        $summary = $migrator->migrate_batch($family, $ids, $execute);
        foreach ($summary->get_results() as $result) {
            $status = $result->get_status();
            $counters[$status] = ($counters[$status] ?? 0) + 1;
            $results[] = $result->to_array();
            if ($verbose || !$result->is_successful()) {
                cli_writeln(sprintf('%-12s #%d %s', $family, $result->get_legacy_id(), $status));
                foreach ($result->get_issues() as $issue) {
                    cli_writeln('  [' . strtoupper($issue->get_severity()) . '] ' . $issue->get_code() . ': ' . $issue->get_message());
                }
            }
        }

        $processed += count($ids);
        $afterid = max($ids);
        $overallfailed = $overallfailed || $summary->has_failures();
        if ($singleid > 0) {
            break;
        }
    } while (true);

    // Immediate independent read-side audit for the processed selection.
    $auditmissing = 0;
    $auditdifferent = 0;
    if ($execute) {
        foreach ($results as $resultdata) {
            if (!in_array($resultdata['status'], [
                CommerceLegacyMigrationResult::STATUS_MIGRATED,
                CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT,
            ], true)) {
                continue;
            }
            $legacyid = (int)$resultdata['legacyid'];
            $legacy = $source->get_by_id($legacyid);
            if ($legacy === null) {
                $auditmissing++;
                continue;
            }
            $expected = $snapshotfactory->create($legacy);
            $actual = $repository->find_by_legacy_reference($family, $legacyid);
            $comparison = $comparator->compare($expected, $actual);
            if ($comparison->get_status() === 'missing_native') {
                $auditmissing++;
            } else if (!$comparison->is_equal()) {
                $auditdifferent++;
            }
        }
    }

    $nativetotal = $DB->count_records(
        CommercePersistenceSchema::TABLE_PURCHASE,
        ['legacyfamily' => $family]
    );
    cli_writeln('Processed: ' . $processed);
    foreach ($counters as $status => $count) {
        cli_writeln('  ' . str_pad($status, 18) . $count);
    }
    if ($execute) {
        cli_writeln('  audit_missing     ' . $auditmissing);
        cli_writeln('  audit_different   ' . $auditdifferent);
        cli_writeln('  native_total      ' . $nativetotal);
    }

    if ($auditmissing > 0 || $auditdifferent > 0) {
        $overallfailed = true;
    }

    $report['families'][$family] = [
        'legacytotal' => $source->count(),
        'processed' => $processed,
        'counters' => $counters,
        'auditmissing' => $auditmissing,
        'auditdifferent' => $auditdifferent,
        'nativetotal' => $nativetotal,
        'results' => $results,
    ];
}

$report['durationms'] = max(0, (int)round((microtime(true) - $started) * 1000));
$report['success'] = !$overallfailed;
$report['completedat'] = time();

if ($reportfile !== '') {
    if (!str_starts_with($reportfile, DIRECTORY_SEPARATOR)) {
        cli_error('--report-file must be an absolute path.');
    }
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (file_put_contents($reportfile, $json . PHP_EOL, LOCK_EX) === false) {
        cli_error('Unable to write report file: ' . $reportfile);
    }
    cli_writeln('Report: ' . $reportfile);
}

if ($overallfailed) {
    cli_writeln('[ERROR] I4D.9 DEV backfill completed with anomalies.');
    exit($strict ? 1 : 0);
}
cli_writeln('[OK] I4D.9 DEV backfill completed and verified successfully.');
exit(0);
