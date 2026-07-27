<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

$manager = (new CommerceCatalogFactory($DB))->product_manager();
$products = $manager->list_products();
$errors = [];
$editable = 0;

foreach ($products as $summary) {
    try {
        $manager->get_editor_data($summary->get_sku());
        $editable++;
    } catch (Throwable $exception) {
        $errors[] = $summary->get_sku() . ': ' . $exception->getMessage();
    }
}

$result = [
    'products' => count($products),
    'editable' => $editable,
    'errors' => $errors,
    'certified' => $errors === [] && $editable === count($products),
];

if (in_array('--json', $argv, true)) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94E4 - Unified Commerce product editor ==\n";
    printf("products:  %d\n", $result['products']);
    printf("editable:  %d\n", $result['editable']);
    printf("errors:    %d\n", count($result['errors']));
    printf("certified: %s\n", $result['certified'] ? 'yes' : 'no');
}

exit($result['certified'] ? 0 : 1);
