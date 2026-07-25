<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceNativePersistenceCertificationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'id' => 0,
    'after-id' => 0,
    'limit' => 0,
    'batch-size' => 100,
    'keep' => false,
    'strict' => false,
    'verbose' => false,
], [
    'h' => 'help',
    'f' => 'family',
    'i' => 'id',
    's' => 'strict',
    'v' => 'verbose',
]);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo <<<HELP
Certify Legacy -> native Commerce persistence round trips.

The default mode removes only native rows created by this certification run.
Pre-existing native rows are compared but never deleted.

Options:
  --family=all|subscription|digital
  --id=ID                 Certify one Legacy identifier.
  --after-id=ID           Start strictly after this identifier.
  --limit=N               Maximum records per family; 0 means all.
  --batch-size=N          Batch size from 1 to 1000.
  --keep                   Keep rows created during certification.
  --strict                 Exit non-zero on difference or failure.
  --verbose                Print one line per purchase.
  --help                   Display this help.

HELP;
    exit(0);
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$service = CommerceNativePersistenceCertificationFactory::create();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
$singleid = max(0, (int)$options['id']);
$afteridoption = max(0, (int)$options['after-id']);
$limit = max(0, (int)$options['limit']);
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$cleanup = empty($options['keep']);
$strict = !empty($options['strict']);
$verbose = !empty($options['verbose']);
$overallfailed = false;

cli_heading('Native Commerce persistence certification');
cli_writeln('Created rows: ' . ($cleanup ? 'CLEANUP AFTER CERTIFICATION' : 'KEEP'));

foreach ($families as $family) {
    $source = $registry->get($family);
    $processed = 0;
    $afterid = $afteridoption;
    $counters = [];

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

        $summary = $service->certify_batch($family, $ids, $cleanup);
        foreach ($summary->get_results() as $result) {
            $status = $result->get_status();
            $counters[$status] = ($counters[$status] ?? 0) + 1;
            if ($verbose || !$result->is_certified()) {
                cli_writeln(sprintf(
                    '%-12s #%d %-10s %dms %s',
                    $family,
                    $result->get_legacy_id(),
                    $status,
                    $result->get_duration_ms(),
                    $result->was_cleaned_up() ? '[cleaned]' : ''
                ));
                if ($result->get_expected_hash() !== null) {
                    cli_writeln('  expected ' . $result->get_expected_hash());
                }
                if ($result->get_actual_hash() !== null) {
                    cli_writeln('  actual   ' . $result->get_actual_hash());
                }
                if ($result->get_error() !== null) {
                    cli_writeln('  error    ' . $result->get_error());
                }
                if ($result->get_differences() !== []) {
                    cli_writeln('  sections ' . implode(', ', array_keys($result->get_differences())));
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

    cli_writeln('Processed: ' . $processed);
    foreach ($counters as $status => $count) {
        cli_writeln('  ' . str_pad($status, 18) . $count);
    }
}

if ($overallfailed) {
    cli_writeln('[ERROR] Native Commerce persistence certification found anomalies.');
    exit($strict ? 1 : 0);
}
cli_writeln('[OK] Native Commerce persistence certification completed successfully.');
exit(0);
