<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityCertificationFinding;
use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityCertificationService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'json' => false,
    'strict' => false,
    'email' => null,
    'sample-limit' => 10,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo <<<HELP
CampusFR Commerce customer identity certification.

Options:
--email=ADDRESS      Restrict purchase checks to one buyer email.
--sample-limit=N     Maximum sample references shown per finding (default: 10, max: 100).
--json               Return a machine-readable JSON report.
--strict             Treat warnings as certification failures.
-h, --help           Show this help.

The command is strictly read-only. It never reconciles or modifies data.
Typical production workflow:
  1. Run this command and archive the report.
  2. Run reconcile_customer_identities.php in dry-run.
  3. Execute reconciliation only after reviewing the dry-run.
  4. Run this certification again and archive the post-reconciliation report.
HELP;
    exit(0);
}

$email = is_string($options['email']) && trim($options['email']) !== '' ? trim($options['email']) : null;
$samplelimit = max(1, min(100, (int)$options['sample-limit']));
$strict = !empty($options['strict']);

$report = (new CommerceCustomerIdentityCertificationService($DB))->certify($email, $samplelimit);

if (!empty($options['json'])) {
    echo json_encode(
        $report->export($strict),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;
    exit($report->is_certified($strict) ? 0 : 1);
}

$markers = [
    CommerceCustomerIdentityCertificationFinding::OK => '[OK]',
    CommerceCustomerIdentityCertificationFinding::WARNING => '[WARN]',
    CommerceCustomerIdentityCertificationFinding::ERROR => '[FAIL]',
];

echo "============================================================\n";
echo "CampusFR Commerce — Customer Identity Certification\n";
echo "============================================================\n\n";

foreach ($report->get_findings() as $finding) {
    echo sprintf("%-6s %s\n", $markers[$finding->severity], $finding->label);
    if ($finding->detail !== '') {
        echo '       ' . $finding->detail . PHP_EOL;
    }
}

echo "\n------------------------------------------------------------\n";
echo "Metrics\n";
foreach ($report->get_metrics() as $key => $value) {
    echo sprintf("  %-34s %d\n", $key, $value);
}

echo "------------------------------------------------------------\n";
echo sprintf(
    "OK=%d WARNINGS=%d ERRORS=%d\n",
    $report->count(CommerceCustomerIdentityCertificationFinding::OK),
    $report->count(CommerceCustomerIdentityCertificationFinding::WARNING),
    $report->count(CommerceCustomerIdentityCertificationFinding::ERROR)
);
echo 'STATUS: ' . ($report->is_certified($strict) ? 'CERTIFIED' : 'NOT CERTIFIED') . PHP_EOL;
if (!$strict && $report->count(CommerceCustomerIdentityCertificationFinding::WARNING) > 0
        && $report->count(CommerceCustomerIdentityCertificationFinding::ERROR) === 0) {
    echo "Note: warnings are non-blocking. Re-run with --strict to make them blocking.\n";
}
echo "============================================================\n";

exit($report->is_certified($strict) ? 0 : 1);
