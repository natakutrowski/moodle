<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyBackfillReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'id' => 0,
    'after-id' => 0,
    'limit' => 0,
    'batch-size' => 100,
    'execute' => false,
    'strict' => false,
    'verbose' => false,
    'report-file' => '',
    'session-id' => '',
    'resume' => false,
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
Migrate Legacy Commerce purchases to native persistence.

Dry-run is the default. Writing requires --execute.

Options:
  --family=all|subscription|digital
  --id=ID                 Process one Legacy identifier.
  --after-id=ID           Start strictly after this identifier.
  --limit=N               Maximum number of records per family; 0 means all.
  --batch-size=N          Batch size from 1 to 1000 (default 100).
  -e, --execute           Persist native snapshots.
  -s, --strict            Exit non-zero on invalid or failed results.
  -v, --verbose           Print one line per purchase.
  --report-file=PATH      Write an atomic JSON report and checkpoint after every batch.
  --session-id=ID         Stable migration session identifier (generated when omitted).
  --resume                Resume from --report-file. Mode, families, batch size and limit must match.
  -h, --help              Display this help.

Production pattern:
  --family=all --batch-size=100 --execute --strict \\
  --report-file=/tmp/campusfr-commerce-backfill.json

Resume pattern:
  Repeat the same command and add --resume.

HELP;
    exit(0);
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$migrator = CommerceLegacyMigrationFactory::create_migrator();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
foreach ($families as $family) {
    $registry->get($family); // Validate before any report is created.
}
$execute = !empty($options['execute']);
$strict = !empty($options['strict']);
$verbose = !empty($options['verbose']);
$resume = !empty($options['resume']);
$singleid = max(0, (int)$options['id']);
$afteridoption = max(0, (int)$options['after-id']);
$limit = max(0, (int)$options['limit']);
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$reportfile = trim((string)$options['report-file']);
$sessionid = trim((string)$options['session-id']);
$overallfailed = false;

if ($resume && $reportfile === '') {
    cli_error('--resume requires --report-file.');
}
if ($resume && $singleid > 0) {
    cli_error('--resume cannot be combined with --id.');
}
if ($resume && $afteridoption > 0) {
    cli_error('--resume cannot be combined with --after-id. The checkpoint supplies the cursor.');
}

try {
    if ($resume) {
        $report = CommerceLegacyBackfillReport::load($reportfile);
        $report->assert_compatible($execute, $families, $batchsize, $limit);
        $sessionid = $report->get_session_id();
    } else {
        if ($sessionid === '') {
            $sessionid = gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }
        $report = CommerceLegacyBackfillReport::start(
            $sessionid,
            $execute,
            $families,
            $batchsize,
            $afteridoption,
            $limit
        );
        if ($reportfile !== '') {
            $report->save($reportfile);
        }
    }
} catch (\Throwable $exception) {
    cli_error('Unable to initialise the backfill report: ' . $exception->getMessage());
}

cli_heading('Legacy Commerce native backfill');
cli_writeln('Session: ' . $sessionid);
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));
cli_writeln('Resume: ' . ($resume ? 'YES' : 'NO'));
if ($reportfile !== '') {
    cli_writeln('Report: ' . $reportfile);
}

foreach ($families as $family) {
    $source = $registry->get($family);
    cli_writeln('');
    cli_writeln('Family: ' . $family . ' (Legacy total: ' . $source->count() . ')');

    if ($resume && $report->is_family_complete($family)) {
        cli_writeln('Already complete in checkpoint; skipped.');
        continue;
    }

    $processedthisrun = 0;
    $afterid = $resume ? $report->get_last_processed_id($family) : $afteridoption;
    $processedtotal = $resume ? $report->get_processed($family) : 0;
    $counters = [];
    $familyfailed = false;

    do {
        if ($singleid > 0) {
            $ids = [$singleid];
        } else {
            $remaining = $limit > 0 ? $limit - $processedtotal : $batchsize;
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
            if ($verbose || !$result->is_successful()) {
                cli_writeln(sprintf('%-12s #%d %s', $family, $result->get_legacy_id(), $status));
                foreach ($result->get_issues() as $issue) {
                    cli_writeln('  [' . strtoupper($issue->get_severity()) . '] ' . $issue->get_code() . ': ' . $issue->get_message());
                }
            }
        }

        $batchcount = count($ids);
        $processedthisrun += $batchcount;
        $processedtotal += $batchcount;
        $afterid = max($ids);
        $checkpointid = $afterid;
        foreach ($summary->get_results() as $result) {
            if (!$result->is_successful()) {
                $checkpointid = min($checkpointid, max(0, $result->get_legacy_id() - 1));
            }
        }
        $report->record_batch($family, $summary->get_results(), $checkpointid);
        if ($reportfile !== '') {
            $report->save($reportfile);
        }
        cli_writeln(sprintf('  checkpoint: processed=%d last_id=%d', $processedtotal, $checkpointid));

        if ($summary->has_failures()) {
            $overallfailed = true;
            $familyfailed = true;
        }
        if ($singleid > 0) {
            break;
        }
    } while (true);

    if (!$familyfailed) {
        $report->mark_family_complete($family);
    }
    if ($reportfile !== '') {
        $report->save($reportfile);
    }
    cli_writeln('Processed this run: ' . $processedthisrun);
    cli_writeln('Processed in session: ' . $report->get_processed($family));
    foreach ($counters as $status => $count) {
        cli_writeln('  ' . str_pad($status, 18) . $count);
    }
}

$report->finish(!$overallfailed);
if ($reportfile !== '') {
    try {
        $report->save($reportfile);
    } catch (\Throwable $exception) {
        cli_error('Unable to finalise the backfill report: ' . $exception->getMessage());
    }
}

if ($overallfailed) {
    cli_writeln('[ERROR] Legacy Commerce migration completed with anomalies.');
    exit($strict ? 1 : 0);
}
cli_writeln('[OK] Legacy Commerce migration completed successfully.');
exit(0);
