<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\mail\certification\CommerceMailCertificationFinding;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;

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

if ($options['help']) {
    echo <<<HELP
CampusFR Commerce transactional mail engine certification.

Options:
--json       Return a machine-readable JSON report.
--strict     Treat warnings as certification failures.
-h, --help   Show this help.

The command is read-only: it does not send mail and does not modify the queue.
HELP;
    exit(0);
}

global $DB;
$strict = !empty($options['strict']);
$report = (new CommerceMailEngineCertificationService($DB))->certify();

if (!empty($options['json'])) {
    echo json_encode(
        $report->export($strict),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;
    exit($report->is_certified($strict) ? 0 : 1);
}

echo "============================================================\n";
echo "CampusFR Commerce Transactional Mailing Engine\n";
echo "Engine Certification\n";
echo "============================================================\n\n";

$markers = [
    CommerceMailCertificationFinding::OK => '[OK]',
    CommerceMailCertificationFinding::WARNING => '[WARN]',
    CommerceMailCertificationFinding::ERROR => '[FAIL]',
];
foreach ($report->get_findings() as $finding) {
    echo sprintf("%-6s %s\n", $markers[$finding->get_severity()], $finding->get_label());
    if ($finding->get_detail() !== '') {
        echo '       ' . $finding->get_detail() . PHP_EOL;
    }
}

echo "\n------------------------------------------------------------\n";
echo sprintf(
    "OK=%d WARNINGS=%d ERRORS=%d\n",
    $report->count(CommerceMailCertificationFinding::OK),
    $report->count(CommerceMailCertificationFinding::WARNING),
    $report->count(CommerceMailCertificationFinding::ERROR)
);
echo 'STATUS: ' . ($report->is_certified($strict) ? 'CERTIFIED' : 'NOT CERTIFIED') . PHP_EOL;
if (!$strict && $report->has_warnings() && !$report->has_errors()) {
    echo "Note: warnings are non-blocking. Re-run with --strict to make them blocking.\n";
}
echo "============================================================\n";

exit($report->is_certified($strict) ? 0 : 1);
