<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFeatureToggle;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognized] = cli_get_params([
    'family' => 'all',
    'id' => 0,
    'batch-size' => 100,
    'execute' => false,
    'confirm-reconcile' => false,
    'strict' => false,
    'verbose' => false,
    'help' => false,
], [
    'f' => 'family',
    'i' => 'id',
    'b' => 'batch-size',
    'x' => 'execute',
    'c' => 'confirm-reconcile',
    's' => 'strict',
    'v' => 'verbose',
    'h' => 'help',
]);

if ($options['help']) {
    echo "Reconcile Legacy Commerce purchases into native persistence.\n\n";
    echo "--family=all|subscription|digital --id=N --batch-size=N\n";
    echo "--execute --confirm-reconcile --strict --verbose\n";
    exit(0);
}

if ($options['execute'] && !$options['confirm-reconcile']) {
    cli_error('--execute requires --confirm-reconcile.');
}

$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? ['subscription', 'digital'] : [$familyoption];
$legacyid = max(0, (int)$options['id']);
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$sources = CommerceLegacyMigrationFactory::create_source_registry();
$service = CommerceDualWriteFactory::create();
$failed = false;

$toggle = new CommerceDualWriteFeatureToggle();
if (!$toggle->is_enabled() && $options['execute']) {
    cli_error(
        'Commerce dual-write is disabled. Enable commerce_native_dual_write_enabled before reconciliation.'
    );
}

echo "== Commerce dual-write reconciliation ==\n";
echo 'Mode: ' . ($options['execute'] ? 'EXECUTE' : 'DRY-RUN') . "\n\n";

foreach ($families as $family) {
    $source = $sources->get($family);
    $ids = $legacyid > 0 ? [$legacyid] : [];

    if ($ids === []) {
        $afterid = 0;
        do {
            $batch = $source->get_ids($afterid, $batchsize);
            foreach ($batch as $id) {
                $ids[] = (int)$id;
                $afterid = max($afterid, (int)$id);
            }
        } while (count($batch) === $batchsize);
    }

    $counts = [];
    foreach ($ids as $id) {
        if (!$options['execute']) {
            $counts['would_reconcile'] = ($counts['would_reconcile'] ?? 0) + 1;
            if ($options['verbose']) {
                echo sprintf("%-12s #%d would_reconcile\n", $family, $id);
            }
            continue;
        }

        try {
            $result = $service->synchronise($family, $id, 'manual_reconciliation');
            $status = $result->get_status();
            $counts[$status] = ($counts[$status] ?? 0) + 1;
            if ($options['verbose']) {
                echo sprintf("%-12s #%d %s\n", $family, $id, $status);
                if ($result->get_differences() !== []) {
                    echo '  ' . json_encode($result->get_differences(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
                }
            }
            if ($status === 'failed') {
                $failed = true;
            }
        } catch (Throwable $exception) {
            $failed = true;
            $counts['failed'] = ($counts['failed'] ?? 0) + 1;
            echo sprintf("%-12s #%d failed: %s\n", $family, $id, $exception->getMessage());
        }
    }

    echo "\nFamily: {$family}\n";
    foreach ($counts as $status => $count) {
        echo sprintf("  %-18s %d\n", $status, $count);
    }
    echo "\n";
}

if ($failed) {
    echo "[ERROR] Reconciliation completed with failures.\n";
    exit($options['strict'] ? 1 : 0);
}

echo "[OK] Reconciliation completed successfully.\n";
