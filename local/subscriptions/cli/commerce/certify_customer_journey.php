<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceCustomerJourneyCertificationFinding;
use local_subscriptions\commerce\certification\CommerceCustomerJourneyCertificationService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'json' => false,
    'strict' => false,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo <<<HELP
CampusFR Commerce final customer-journey certification.

Options:
--json       Return a machine-readable JSON report.
--strict     Treat warnings as certification failures.
-h, --help   Show this help.

The command is read-only. It does not create or modify purchases, payments,
grants, enrolments, messages, carts, support requests, or CRM records.
HELP;
    exit(0);
}

global $DB;
$strict = !empty($options['strict']);
$report = (new CommerceCustomerJourneyCertificationService($DB))->certify();

if (!empty($options['json'])) {
    echo json_encode(
        $report->export($strict),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;
    exit($report->is_certified($strict) ? 0 : 1);
}

$markers = [
    CommerceCustomerJourneyCertificationFinding::OK => '[OK]',
    CommerceCustomerJourneyCertificationFinding::WARNING => '[WARN]',
    CommerceCustomerJourneyCertificationFinding::ERROR => '[FAIL]',
];

echo "============================================================\n";
echo "CampusFR Commerce — Final Customer Journey Certification\n";
echo "============================================================\n\n";

foreach ($report->get_findings() as $finding) {
    echo sprintf("%-6s %s\n", $markers[$finding->get_severity()], $finding->get_label());
    if ($finding->get_detail() !== '') {
        echo '       ' . $finding->get_detail() . PHP_EOL;
    }
}

echo "\n------------------------------------------------------------\n";
echo sprintf(
    "OK=%d WARNINGS=%d ERRORS=%d\n",
    $report->count(CommerceCustomerJourneyCertificationFinding::OK),
    $report->count(CommerceCustomerJourneyCertificationFinding::WARNING),
    $report->count(CommerceCustomerJourneyCertificationFinding::ERROR)
);
echo 'STATUS: ' . ($report->is_certified($strict) ? 'CERTIFIED' : 'NOT CERTIFIED') . PHP_EOL;
if (!$strict && $report->count(CommerceCustomerJourneyCertificationFinding::WARNING) > 0
        && $report->count(CommerceCustomerJourneyCertificationFinding::ERROR) === 0) {
    echo "Note: warnings are non-blocking. Re-run with --strict to make them blocking.\n";
}
echo "============================================================\n";

exit($report->is_certified($strict) ? 0 : 1);
