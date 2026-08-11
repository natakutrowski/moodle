<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\course\CommerceCoursePurchaseCertifier;

[$options, $unrecognized] = cli_get_params([
    'purchase' => '',
    'json' => false,
    'help' => false,
], [
    'p' => 'purchase',
    'j' => 'json',
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help'] || trim((string)$options['purchase']) === '') {
    echo <<<HELP
Commerce 7.95 H4.7 — certify a Native course purchase.

Options:
  --purchase=REFERENCE   Native Commerce Purchase reference (required)
  --json                 Output JSON
  --help                 Show this help

Examples:
  php local/subscriptions/cli/commerce/certification/certify_course_purchase.php --purchase=cmp_xxx
  php local/subscriptions/cli/commerce/certification/certify_course_purchase.php --purchase=cmp_xxx --json
HELP;
    exit($options['help'] ? 0 : 1);
}

$report = (new CommerceCoursePurchaseCertifier($DB))->certify((string)$options['purchase']);

if ($options['json']) {
    echo json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($report->certified ? 0 : 2);
}

echo "== Commerce 7.95 H4.7 — Course purchase certification ==\n";
echo 'Purchase: ' . $report->purchasereference . "\n\n";
foreach ($report->checks as $check) {
    echo sprintf('[%s] %s — %s', $check['status'], $check['key'], $check['message']) . "\n";
}
echo "\nVerdict: " . ($report->certified ? 'CERTIFIED' : 'NOT CERTIFIED') . "\n";
exit($report->certified ? 0 : 2);
