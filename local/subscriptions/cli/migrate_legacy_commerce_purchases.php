<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

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
  -h, --help              Display this help.

HELP;
    exit(0);
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$migrator = CommerceLegacyMigrationFactory::create_migrator();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
$execute = !empty($options['execute']);
$strict = !empty($options['strict']);
$verbose = !empty($options['verbose']);
$singleid = max(0, (int)$options['id']);
$afteridoption = max(0, (int)$options['after-id']);
$limit = max(0, (int)$options['limit']);
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$overallfailed = false;

cli_heading('Legacy Commerce native backfill');
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));

foreach ($families as $family) {
    $source = $registry->get($family);
    cli_writeln('');
    cli_writeln('Family: ' . $family . ' (Legacy total: ' . $source->count() . ')');

    $processed = 0;
    $afterid = $afteridoption;
    $counters = [];

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
            if ($verbose || !$result->is_successful()) {
                cli_writeln(sprintf('%-12s #%d %s', $family, $result->get_legacy_id(), $status));
                foreach ($result->get_issues() as $issue) {
                    cli_writeln('  [' . strtoupper($issue->get_severity()) . '] ' . $issue->get_code() . ': ' . $issue->get_message());
                }
            }
        }

        $processed += count($ids);
        $afterid = max($ids);
        if ($summary->has_failures()) {
            $overallfailed = true;
        }
        if ($singleid > 0) {
            break;
        }
    } while (true);

    cli_writeln('Processed: ' . $processed);
    foreach ($counters as $status => $count) {
        cli_writeln('  ' . str_pad($status, 18) . $count);
    }
}

if ($overallfailed) {
    cli_writeln('[ERROR] Legacy Commerce migration completed with anomalies.');
    exit($strict ? 1 : 0);
}
cli_writeln('[OK] Legacy Commerce migration completed successfully.');
exit(0);
