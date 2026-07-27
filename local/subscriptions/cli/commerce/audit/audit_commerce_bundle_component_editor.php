<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

$manager = (new CommerceCatalogFactory($DB))->product_manager();
$bundles = $manager->list_products('bundle');
$previewed = 0;
$errors = [];

foreach ($bundles as $summary) {
    try {
        $editor = $manager->get_editor_data($summary->get_sku());
        if ($editor->get_expansion() !== null) {
            $previewed++;
        }
    } catch (Throwable $exception) {
        $errors[] = $summary->get_sku() . ': ' . $exception->getMessage();
    }
}

$result = [
    'bundles' => count($bundles),
    'previewed' => $previewed,
    'errors' => $errors,
    'certified' => $errors === [] && $previewed === count($bundles),
];

if (in_array('--json', $argv, true)) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94E5 - Bundle component editor ==\n";
    printf("bundles:   %d\n", $result['bundles']);
    printf("previewed: %d\n", $result['previewed']);
    printf("errors:    %d\n", count($result['errors']));
    printf("certified: %s\n", $result['certified'] ? 'yes' : 'no');
}

exit($result['certified'] ? 0 : 1);
