<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

$help = in_array('--help', $argv, true) || in_array('-h', $argv, true);
$json = in_array('--json', $argv, true);
$strict = in_array('--strict', $argv, true);

if ($help) {
    echo "Audit CRM Bundle preview.\n\n";
    echo "Options: --json --strict --help\n";
    exit(0);
}

$report = (new CommerceCatalogFactory($DB))->bundle_preview_auditor()->audit();

if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94E6 - Bundle CRM preview ==\n";
    printf("bundles:      %d\n", $report['bundles']);
    printf("previewed:    %d\n", $report['previewed']);
    printf("products:     %d\n", $report['products']);
    printf("entitlements: %d\n", $report['entitlements']);
    printf("prices:       %d\n", $report['prices']);
    printf("errors:       %d\n", count($report['errors']));
    printf("certified:    %s\n", $report['certified'] ? 'yes' : 'no');
}

exit($strict && !$report['certified'] ? 1 : 0);
