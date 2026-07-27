<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
$json = in_array('--json', $argv, true);
$strict = in_array('--strict', $argv, true);
$report = (new CommerceCatalogFactory($DB))->bundle_pricing_auditor()->audit();
if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94E7 - Bundle pricing ==\n";
    printf("bundles:    %d\n", $report['bundles']);
    printf("configured: %d\n", $report['configured']);
    printf("quotes:     %d\n", $report['quotes']);
    printf("errors:     %d\n", count($report['errors']));
    printf("certified:  %s\n", $report['certified'] ? 'yes' : 'no');
}
exit($strict && !$report['certified'] ? 1 : 0);
