<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\read\CommerceNativeReadFactory;

[$options, $unrecognized] = cli_get_params([
    'family' => 'all',
    'id' => 0,
    'batch-size' => 500,
    'limit' => 0,
    'verbose' => false,
    'strict' => false,
    'help' => false,
], [
    'f' => 'family',
    'i' => 'id',
    'b' => 'batch-size',
    'l' => 'limit',
    'v' => 'verbose',
    's' => 'strict',
    'h' => 'help',
]);

if ($options['help']) {
    echo "Audit I6 native Commerce shadow reads.\n\n";
    echo "--family=all|subscription|digital --id=N --batch-size=N --limit=N --verbose --strict\n";
    exit(0);
}

if (empty(get_config('local_subscriptions', 'commerce_native_read_shadow_enabled'))) {
    cli_error('Native Commerce shadow reads are disabled. Enable commerce_native_read_shadow_enabled first.');
}
$families = $options['family'] === 'all' ? ['subscription', 'digital'] : [strtolower((string)$options['family'])];
$sources = CommerceLegacyMigrationFactory::create_source_registry();
$service = CommerceNativeReadFactory::create();
$failed = false;
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$limit = max(0, (int)$options['limit']);
$onlyid = max(0, (int)$options['id']);

echo "== I6 Commerce native shadow-read audit ==\n";
foreach ($families as $family) {
    $source = $sources->get($family);
    $counts = ['equal' => 0, 'missing_native' => 0, 'different' => 0, 'invalid_legacy' => 0];
    $ids = $onlyid > 0 ? [$onlyid] : [];
    if ($onlyid === 0) {
        $afterid = 0;
        while (true) {
            $remaining = $limit > 0 ? $limit - count($ids) : $batchsize;
            if ($limit > 0 && $remaining <= 0) { break; }
            $batch = $source->get_ids($afterid, min($batchsize, $remaining));
            if ($batch === []) { break; }
            foreach ($batch as $id) {
                $ids[] = (int)$id;
                $afterid = max($afterid, (int)$id);
            }
            if (count($batch) < min($batchsize, $remaining)) { break; }
        }
    }

    foreach ($ids as $id) {
        try {
            $result = $service->read($family, (int)$id, 'cli_audit');
            $counts[$result->get_status()] = ($counts[$result->get_status()] ?? 0) + 1;
            if ($result->has_issue()) {
                $failed = true;
                if ($options['verbose']) {
                    echo sprintf("%-12s #%d %s\n", $family, $id, $result->get_status());
                    foreach ($result->get_differences() as $section => $difference) {
                        echo '  [' . $section . '] ' . json_encode($difference, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    }
                }
            }
        } catch (Throwable $exception) {
            $counts['invalid_legacy']++;
            $failed = true;
            if ($options['verbose']) {
                echo sprintf("%-12s #%d error: %s\n", $family, $id, $exception->getMessage());
            }
        }
    }

    echo "\nFamily: {$family}\n";
    foreach ($counts as $label => $value) {
        echo sprintf("  %-18s %d\n", $label, $value);
    }
}

$metrics = $service->get_metrics()->to_array();
echo "\nMetrics\n";
foreach ($metrics as $label => $value) {
    echo sprintf("  %-22s %s\n", $label, (string)$value);
}

if ($failed) {
    echo "[ERROR] Native Commerce shadow-read inconsistencies detected.\n";
    exit($options['strict'] ? 1 : 0);
}

echo "[OK] Native Commerce shadow reads match Legacy.\n";
