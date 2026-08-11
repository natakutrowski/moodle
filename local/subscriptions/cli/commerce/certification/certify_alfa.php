<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\payment\CommerceAlfaConfigurationCertifier;
use local_subscriptions\commerce\certification\payment\CommerceAlfaPurchaseCertifier;

[$options, $unrecognized] = cli_get_params(['purchase' => '', 'json' => false, 'help' => false], ['p' => 'purchase', 'j' => 'json', 'h' => 'help']);
if ($unrecognized) { cli_error('Unknown options: ' . implode(', ', $unrecognized)); }
if ($options['help']) {
    echo "Commerce 7.95 H4.10 — Alfa certification\n\n";
    echo "  --purchase=REFERENCE  Also certify an actual paid Alfa Purchase\n  --json                Output JSON\n  --help                Show help\n";
    exit(0);
}

$configuration = (new CommerceAlfaConfigurationCertifier())->certify();
$purchase = null;
if (trim((string)$options['purchase']) !== '') {
    $purchase = (new CommerceAlfaPurchaseCertifier($DB))->certify((string)$options['purchase']);
}
$certified = $configuration['certified'] && ($purchase === null || $purchase['certified']);
$result = ['phase' => '7.95H4.10', 'provider' => 'alfa', 'certified' => $certified, 'configuration' => $configuration, 'purchase' => $purchase];

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($certified ? 0 : 2);
}

echo "== Commerce 7.95 H4.10 — Alfa certification ==\n\n";
foreach ($configuration['checks'] as $check) { echo "[{$check['status']}] configuration.{$check['key']} — {$check['message']}\n"; }
if ($purchase !== null) {
    echo "\nPurchase: {$purchase['purchase_reference']}\n";
    foreach ($purchase['checks'] as $check) { echo "[{$check['status']}] purchase.{$check['key']} — {$check['message']}\n"; }
}
echo "\nVerdict: " . ($certified ? 'ALFA CERTIFIED' : 'ALFA NOT CERTIFIED') . "\n";
exit($certified ? 0 : 2);
