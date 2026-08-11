<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\personaloffer\certification\CommercePersonalOfferCertificationService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'campaign' => '',
    'strict' => false,
    'json' => false,
    'sample-limit' => 10,
], ['h' => 'help']);

if ($unrecognized || $options['help']) {
    echo "Personal Offer certification (read-only)\n\n"
        . "Options:\n"
        . "  --campaign=KEY      Limit certification to one campaign\n"
        . "  --strict            Treat warnings as certification failures\n"
        . "  --json              Emit machine-readable JSON\n"
        . "  --sample-limit=N    Maximum samples per check (default 10)\n";
    exit($unrecognized ? 1 : 0);
}

$service = CommercePersonalOfferCertificationService::create($DB);
$result = $service->certify(
    trim((string)$options['campaign']) !== '' ? trim((string)$options['campaign']) : null,
    (bool)$options['strict'],
    max(0, (int)$options['sample-limit'])
);

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($result['certified'] ? 0 : 1);
}

echo "Commerce Personal Offer certification\n";
echo "====================================\n";
echo "Scope: " . ($result['campaign'] ?: 'all campaigns') . "\n";
echo "Mode : " . ($result['strict'] ? 'STRICT' : 'normal') . "\n\n";

foreach ($result['metrics'] as $key => $value) {
    echo str_pad($key, 28) . ": {$value}\n";
}
echo "\n";
foreach ($result['checks'] as $check) {
    $label = $check['count'] === 0 ? 'OK' : strtoupper($check['severity']);
    echo '[' . str_pad($label, 7) . '] ' . str_pad($check['key'], 32) . $check['count'] . "\n";
    foreach ($check['samples'] as $sample) {
        echo '           - ' . json_encode($sample, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
echo "\nErrors: {$result['errors']} | Warnings: {$result['warnings']}\n";
echo $result['certified'] ? "CERTIFIED\n" : "NOT CERTIFIED\n";
exit($result['certified'] ? 0 : 1);
