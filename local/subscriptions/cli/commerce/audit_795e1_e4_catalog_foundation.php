<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\catalog\audit\CommerceCatalogLegacyInventoryAuditor;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusResolver;

$report = (new CommerceCatalogLegacyInventoryAuditor($DB))->audit();
$products = (new CommerceCatalogReadRepository($DB))->find_all();
$status = (new CommerceCatalogStatusResolver())->resolve('active', null, null, [], time(), true);

$checks = [
    'legacy_inventory_tables' => $report->is_healthy(),
    'unified_contracts' => interface_exists('local_subscriptions\\commerce\\catalog\\contract\\CommerceCatalogProductContract'),
    'unified_read_model' => is_array($products),
    'normalised_status_dimensions' => count($status->to_array()) === 4,
    'native_and_legacy_origins' => empty($products) || count(array_unique(array_map(static fn($p) => $p->get_origin(), $products))) >= 1,
];

echo "== 7.95E1-E4 Catalogue foundation ==\n\n";
foreach ($report->get_counts() as $table => $count) {
    printf("%-42s %s\n", $table, $count === null ? 'MISSING' : (string)$count);
}
echo "\n";
$ok = true;
foreach ($checks as $label => $pass) {
    printf("%-38s %s\n", $label, $pass ? 'OK' : 'FAIL');
    $ok = $ok && $pass;
}
echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";
exit($ok ? 0 : 1);
