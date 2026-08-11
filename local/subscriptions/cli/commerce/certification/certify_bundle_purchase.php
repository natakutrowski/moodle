<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\bundle\CommerceBundlePurchaseCertifier;

[$options, $unrecognized] = cli_get_params([
    'purchase' => '',
    'scenario' => 'auto',
    'json' => false,
    'help' => false,
], [
    'p' => 'purchase',
    's' => 'scenario',
    'j' => 'json',
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

$scenario = strtolower(trim((string)$options['scenario']));
$validscenarios = ['auto', 'mixed', 'courses', 'digitals'];
if (!in_array($scenario, $validscenarios, true)) {
    cli_error('Invalid scenario. Expected: ' . implode(', ', $validscenarios));
}

if ($options['help'] || trim((string)$options['purchase']) === '') {
    echo <<<HELP
Commerce 7.95 H4.9 — certify a Native bundle purchase.

Options:
  --purchase=REFERENCE   Native Commerce Purchase reference (required)
  --scenario=TYPE        auto, mixed, courses or digitals (default: auto)
  --json                 Output JSON
  --help                 Show this help

Examples:
  php local/subscriptions/cli/commerce/certification/certify_bundle_purchase.php --purchase=cmp_xxx
  php local/subscriptions/cli/commerce/certification/certify_bundle_purchase.php --purchase=cmp_xxx --scenario=mixed
  php local/subscriptions/cli/commerce/certification/certify_bundle_purchase.php --purchase=cmp_xxx --scenario=digitals --json
HELP;
    exit($options['help'] ? 0 : 1);
}

$report = (new CommerceBundlePurchaseCertifier($DB))->certify(
    (string)$options['purchase'],
    $scenario
);

if ($options['json']) {
    echo json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($report->certified ? 0 : 2);
}

echo "== Commerce 7.95 H4.9 — Bundle purchase certification ==\n";
echo 'Purchase: ' . $report->purchasereference . "\n";
echo 'Scenario: ' . $report->scenario . "\n\n";
foreach ($report->checks as $check) {
    echo sprintf('[%s] %s — %s', $check['status'], $check['key'], $check['message']) . "\n";
}
echo "\nVerdict: " . ($report->certified ? 'CERTIFIED' : 'NOT CERTIFIED') . "\n";
exit($report->certified ? 0 : 2);
