<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\audit\CommerceShadowG1G3Auditor;

[$options] = cli_get_params(['json' => false, 'strict' => false], ['j' => 'json', 's' => 'strict']);
$report = (new CommerceShadowG1G3Auditor(__DIR__ . '/..'))->audit();

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94G1-G3 - Native Fulfillment Shadow Foundation ==\n\n";
    foreach ($report['checks'] as $check => $ok) {
        printf("%-20s %s\n", $check . ':', $ok ? 'OK' : 'FAIL');
    }
    echo 'errors:              ' . count($report['errors']) . PHP_EOL;
    foreach ($report['errors'] as $error) {
        echo ' - ' . $error . PHP_EOL;
    }
    echo 'certified:           ' . ($report['certified'] ? 'yes' : 'no') . PHP_EOL;
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
