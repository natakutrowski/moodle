<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\digital\CommerceDigitalPurchaseCertifier;

[$options] = cli_get_params(['purchase' => null, 'json' => false, 'help' => false], ['p' => 'purchase', 'j' => 'json', 'h' => 'help']);
if ($options['help'] || empty($options['purchase'])) {
    echo "Certify a Native digital purchase.\n\n--purchase=cmp_xxx  Purchase reference\n--json              JSON output\n";
    exit($options['help'] ? 0 : 1);
}
$report = (new CommerceDigitalPurchaseCertifier($DB))->certify((string)$options['purchase']);
if ($options['json']) {
    echo json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Commerce 7.95 H4.8 — Digital purchase certification ==\nPurchase: {$report->purchasereference}\n\n";
    foreach ($report->checks as $check) {
        echo '[' . $check['status'] . '] ' . $check['key'] . ' — ' . $check['message'] . PHP_EOL;
    }
    echo "\nVerdict: " . ($report->certified ? 'CERTIFIED' : 'NOT CERTIFIED') . PHP_EOL;
}
exit($report->certified ? 0 : 2);
