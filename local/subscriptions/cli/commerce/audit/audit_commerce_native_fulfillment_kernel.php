<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\fulfillment\native\audit\CommerceNativeFulfillmentKernelAuditor;

[$options] = cli_get_params(
    ['json' => false, 'strict' => false],
    ['j' => 'json', 's' => 'strict']
);

$report = (new CommerceNativeFulfillmentKernelAuditor(__DIR__ . '/..'))->audit();

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "== Phase 7.94F1 - Native Fulfillment Kernel ==\n\n";
    foreach ($report['checks'] as $name => $ok) {
        echo str_pad($name . ':', 20) . ($ok ? 'OK' : 'FAIL') . "\n";
    }
    echo str_pad('errors:', 20) . count($report['errors']) . "\n";
    foreach ($report['errors'] as $error) {
        echo ' - ' . $error . "\n";
    }
    echo "\n" . str_pad('certified:', 20) . ($report['certified'] ? 'yes' : 'no') . "\n";
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
