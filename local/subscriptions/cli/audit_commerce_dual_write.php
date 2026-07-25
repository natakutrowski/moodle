<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

[$options, $unrecognized] = cli_get_params([
    'family' => 'all',
    'limit' => 0,
    'strict' => false,
    'verbose' => false,
    'help' => false,
], [
    'f' => 'family',
    'l' => 'limit',
    's' => 'strict',
    'v' => 'verbose',
    'h' => 'help',
]);

if ($options['help']) {
    echo "Audit I5 dual-write consistency.\n\n";
    echo "--family=all|subscription|digital --limit=N --strict --verbose\n";
    exit(0);
}

$families = $options['family'] === 'all' ? ['subscription', 'digital'] : [strtolower($options['family'])];
$sources = CommerceLegacyMigrationFactory::create_source_registry();
$factory = new CommerceLegacySnapshotFactory();
$repository = CommercePurchaseSqlRepositoryFactory::create();
$comparator = new CommerceLegacyNativeComparator();
$failed = false;

echo "== I5 Commerce dual-write audit ==\n";
foreach ($families as $family) {
    $source = $sources->get($family);
    $requestedlimit = max(0, (int)$options['limit']);
    $ids = [];
    $afterid = 0;
    do {
        $remaining = $requestedlimit > 0 ? $requestedlimit - count($ids) : 500;
        if ($requestedlimit > 0 && $remaining <= 0) {
            break;
        }
        $batch = $source->get_ids($afterid, min(500, $remaining));
        if ($batch === []) {
            break;
        }
        foreach ($batch as $id) {
            $ids[] = (int)$id;
            $afterid = max($afterid, (int)$id);
        }
    } while (count($batch) === min(500, $remaining));
    $counts = ['equal' => 0, 'missing' => 0, 'different' => 0, 'invalid' => 0];
    foreach ($ids as $id) {
        try {
            $purchase = $source->get_by_id((int)$id);
            if ($purchase === null) {
                $counts['invalid']++;
                continue;
            }
            $expected = $factory->create($purchase);
            $actual = $repository->find_by_legacy_reference($family, (int)$id);
            $comparison = $comparator->compare($expected, $actual);
            if ($comparison->is_equal()) {
                $counts['equal']++;
            } else if ($actual === null) {
                $counts['missing']++;
                $failed = true;
            } else {
                $counts['different']++;
                $failed = true;
            }
            if ($options['verbose'] && !$comparison->is_equal()) {
                echo sprintf("%-12s #%d %s\n", $family, $id, $actual === null ? 'missing' : 'different');
                foreach ($comparison->get_differences() as $section => $difference) {
                    echo '  [' . $section . '] ' . json_encode(
                        $difference,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ) . PHP_EOL;
                }
            }
        } catch (Throwable $e) {
            $counts['invalid']++;
            $failed = true;
            if ($options['verbose']) {
                echo sprintf("%-12s #%d invalid: %s\n", $family, $id, $e->getMessage());
            }
        }
    }
    echo "\nFamily: {$family}\n";
    foreach ($counts as $label => $value) {
        echo sprintf("  %-12s %d\n", $label, $value);
    }
}

if ($failed) {
    echo "[ERROR] Dual-write consistency issues detected.\n";
    exit($options['strict'] ? 1 : 0);
}

echo "[OK] Native Commerce is aligned with Legacy.\n";
