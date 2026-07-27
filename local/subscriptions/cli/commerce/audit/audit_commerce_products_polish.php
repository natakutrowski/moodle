<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\audit\CommerceProductsPolishAuditor;

[$options] = cli_get_params(
    ['json' => false, 'strict' => false],
    ['j' => 'json', 's' => 'strict']
);

$report = (new CommerceProductsPolishAuditor(__DIR__ . '/..'))->audit();

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "== Phase 7.94E12 - Commerce Products Polish ==\n\n";
    foreach ($report['checks'] as $name => $ok) {
        echo str_pad($name . ':', 22) . ($ok ? 'OK' : 'FAIL') . "\n";
    }
    echo str_pad('languages:', 22) . $report['languages'] . "\n";
    echo str_pad('errors:', 22) . count($report['errors']) . "\n";
    foreach ($report['errors'] as $error) {
        echo ' - ' . $error . "\n";
    }
    echo "\n" . str_pad('certified:', 22) . ($report['certified'] ? 'yes' : 'no') . "\n";
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
