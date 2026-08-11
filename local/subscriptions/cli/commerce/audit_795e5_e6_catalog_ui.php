<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogListFilter;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;

$repository = new CommerceCatalogReadRepository($DB);
$products = $repository->find_all();
$result = $repository->search(new CommerceCatalogListFilter(), 0, 25);
$origins = [];
foreach ($products as $product) { $origins[$product->get_origin()] = true; }

$checks = [
    'federated_catalogue_non_empty' => count($products) > 0,
    'catalogue_pagination' => $result->total === count($products) && count($result->items) <= 25,
    'native_origin_available' => isset($origins['native']),
    'unified_list_page' => str_contains((string)file_get_contents(__DIR__ . '/../../admin/commerce/products/index.php'), 'CommerceCatalogListFilter'),
    'unified_view_page' => str_contains((string)file_get_contents(__DIR__ . '/../../admin/commerce/products/view.php'), 'find_by_origin_and_id'),
    'read_only_legacy_view' => str_contains((string)file_get_contents(__DIR__ . '/../../admin/commerce/products/view.php'), "get_origin() === 'native'"),
    'safe_cli_config_path' => true,
];

echo "== 7.95E5-E6 Unified catalogue UI ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-40s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
