<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadFactory;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadMode;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadResult;

[$options, $unrecognized] = cli_get_params([
    'mode' => 'auto', 'family' => 'all', 'id' => 0, 'batch-size' => 500,
    'limit' => 0, 'verbose' => false, 'strict' => false, 'help' => false,
], ['m'=>'mode','f'=>'family','i'=>'id','b'=>'batch-size','l'=>'limit','v'=>'verbose','s'=>'strict','h'=>'help']);
if ($options['help']) {
    echo "Audit I7 Commerce runtime reads.

";
    echo "--mode=legacy|shadow|native|auto --family=all|subscription|digital --id=N --batch-size=N --limit=N --verbose --strict
";
    exit(0);
}
$mode = CommerceRuntimeReadMode::normalise((string)$options['mode']);
$families = $options['family'] === 'all' ? ['subscription', 'digital'] : [strtolower((string)$options['family'])];
$sources = CommerceLegacyMigrationFactory::create_source_registry();
$service = CommerceRuntimeReadFactory::create($mode, false);
$failed = false;
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$limit = max(0, (int)$options['limit']);
$onlyid = max(0, (int)$options['id']);
echo "== I7 Commerce runtime-read audit ==
Mode: {$mode}
";
foreach ($families as $family) {
    $ids = $onlyid > 0 ? [$onlyid] : [];
    if ($onlyid === 0) {
        $afterid = 0;
        while (true) {
            $remaining = $limit > 0 ? $limit - count($ids) : $batchsize;
            if ($limit > 0 && $remaining <= 0) { break; }
            $batch = $sources->get($family)->get_ids($afterid, min($batchsize, $remaining));
            if ($batch === []) { break; }
            foreach ($batch as $id) { $ids[] = (int)$id; $afterid = max($afterid, (int)$id); }
            if (count($batch) < min($batchsize, $remaining)) { break; }
        }
    }
    $counts = ['ok'=>0,'fallback'=>0,'missing'=>0,'different'=>0,'invalid'=>0];
    foreach ($ids as $id) {
        $result = $service->read($family, $id, 'cli_runtime_audit');
        $counts[$result->get_status()] = ($counts[$result->get_status()] ?? 0) + 1;
        if ($result->has_issue()) {
            $failed = true;
            if ($options['verbose']) {
                echo sprintf("%-12s #%d status=%s source=%s fallback=%s
", $family, $id,
                    $result->get_status(), $result->get_source(), $result->used_fallback() ? 'yes' : 'no');
            }
        }
    }
    echo "
Family: {$family}
";
    foreach ($counts as $label=>$value) { echo sprintf("  %-18s %d
", $label, $value); }
}
echo "
Metrics
";
foreach ($service->get_metrics()->to_array() as $label=>$value) { echo sprintf("  %-22s %s
", $label, (string)$value); }
if ($failed) {
    echo "[WARN] Runtime read issues or fallbacks detected.
";
    exit($options['strict'] ? 1 : 0);
}
echo "[OK] Commerce runtime reads completed without issues.
";
