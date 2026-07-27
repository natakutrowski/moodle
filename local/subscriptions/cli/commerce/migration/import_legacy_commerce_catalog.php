<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'execute' => false,
    'json' => false,
], [
    'h' => 'help',
    'f' => 'family',
    'e' => 'execute',
    'j' => 'json',
]);

if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "Import the Legacy subscription and digital catalogues into Native Commerce.\n\n";
    echo "Dry-run is the default. Use --execute to write.\n";
    echo "Options: --family=all|subscription|digital --execute --json\n";
    exit(0);
}

$factory = new CommerceCatalogFactory($DB);
$report = $factory->importer()->import((string)$options['family'], (bool)$options['execute']);
$report['mode'] = $options['execute'] ? 'execute' : 'dry-run';

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    cli_heading('Phase 7.94B6 - Legacy catalogue import');
    foreach (['mode', 'processed', 'createdorupdated', 'prices', 'translations', 'entitlements'] as $key) {
        mtrace(sprintf('%-20s %s', $key . ':', (string)$report[$key]));
    }
    mtrace('Errors: ' . count($report['errors']));
    foreach ($report['errors'] as $error) {
        mtrace('  - ' . $error);
    }
}

exit($report['errors'] === [] ? 0 : 2);
