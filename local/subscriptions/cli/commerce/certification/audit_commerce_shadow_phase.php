<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\audit\CommerceShadowPhaseAuditor;

[$options] = cli_get_params(['json' => false, 'strict' => false], ['j' => 'json', 's' => 'strict']);
$result = (new CommerceShadowPhaseAuditor($CFG->dirroot . '/local/subscriptions'))->audit();
if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94G - Native Fulfillment Shadow ==\n\n";
    foreach ($result['checks'] as $name => $ok) {
        printf("%-20s %s\n", $name . ':', $ok ? 'OK' : 'FAIL');
    }
    echo 'errors:              ' . count($result['errors']) . PHP_EOL;
    foreach ($result['errors'] as $error) {
        echo ' - ' . $error . PHP_EOL;
    }
    echo 'certified:           ' . ($result['certified'] ? 'yes' : 'no') . PHP_EOL;
}
if ($options['strict'] && !$result['certified']) {
    exit(1);
}
