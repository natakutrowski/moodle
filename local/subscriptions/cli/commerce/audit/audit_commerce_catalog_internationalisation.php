<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\audit\CommerceCatalogInternationalisationAuditor;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options] = cli_get_params(['json' => false, 'strict' => false], ['j' => 'json', 's' => 'strict']);
$factory = new CommerceCatalogFactory($DB);
$report = (new CommerceCatalogInternationalisationAuditor(
    $factory->product_manager(),
    $factory->locale_service(),
    $factory->currency_service()
))->audit();

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "== Phase 7.94E11 - International Commerce catalogue ==\n";
    echo 'languages:  ' . $report['languages'] . "\n";
    echo 'bundles:    ' . $report['bundles'] . "\n";
    echo 'currencies: ' . $report['currencies'] . "\n";
    echo 'errors:     ' . count($report['errors']) . "\n";
    echo 'certified:  ' . ($report['certified'] ? 'yes' : 'no') . "\n";
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
