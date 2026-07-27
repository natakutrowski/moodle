<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\audit\CommerceShadowG4G6Auditor;

[$options] = cli_get_params(['json' => false, 'strict' => false], ['j' => 'json', 's' => 'strict']);
$result = (new CommerceShadowG4G6Auditor($CFG->dirroot . '/local/subscriptions'))->audit();
if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94G4-G6 - Fulfillment Shadow Runtime ==

";
    foreach ($result['checks'] as $name => $ok) { printf("%-20s %s
", $name . ':', $ok ? 'OK' : 'FAIL'); }
    echo 'errors:              ' . count($result['errors']) . PHP_EOL;
    foreach ($result['errors'] as $error) { echo ' - ' . $error . PHP_EOL; }
    echo 'certified:           ' . ($result['certified'] ? 'yes' : 'no') . PHP_EOL;
}
if ($options['strict'] && !$result['certified']) { exit(1); }
