<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\mail\certification\CommerceMailCertificationFinding;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\commerce\mail\certification\CommerceMailReleaseManifest;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'json' => false,
    'strict' => true,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo <<<HELP
CampusFR Commerce transactional mail engine final release certification.

Options:
--json          Return a machine-readable JSON report.
--strict=0      Keep engine warnings non-blocking (strict mode is enabled by default).
-h, --help      Show this help.

The command is read-only: it does not send mail and does not modify the queue.
HELP;
    exit(0);
}

global $CFG, $DB;
$strict = !isset($options['strict']) || (bool)$options['strict'];
$enginereport = (new CommerceMailEngineCertificationService($DB))->certify();
$pluginroot = $CFG->dirroot . '/local/subscriptions';
$missingfiles = CommerceMailReleaseManifest::missing_files($pluginroot);
$certified = $enginereport->is_certified($strict) && $missingfiles === [];

if (!empty($options['json'])) {
    echo json_encode([
        'certified' => $certified,
        'manifest' => CommerceMailReleaseManifest::export(),
        'engine' => $enginereport->export($strict),
        'missingfiles' => $missingfiles,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($certified ? 0 : 1);
}

echo "============================================================\n";
echo "CampusFR Commerce Transactional Mailing Engine\n";
echo "Final Release Certification\n";
echo "============================================================\n\n";
echo 'Release:   ' . CommerceMailReleaseManifest::RELEASE . PHP_EOL;
echo 'Status:    ' . CommerceMailReleaseManifest::STATUS . PHP_EOL;
echo 'Lifecycle: ' . CommerceMailReleaseManifest::LIFECYCLE . PHP_EOL;
echo 'Frozen on: ' . CommerceMailReleaseManifest::FROZEN_ON . PHP_EOL . PHP_EOL;

$markers = [
    CommerceMailCertificationFinding::OK => '[OK]',
    CommerceMailCertificationFinding::WARNING => '[WARN]',
    CommerceMailCertificationFinding::ERROR => '[FAIL]',
];
foreach ($enginereport->get_findings() as $finding) {
    echo sprintf("%-6s %s\n", $markers[$finding->get_severity()], $finding->get_label());
    if ($finding->get_detail() !== '') {
        echo '       ' . $finding->get_detail() . PHP_EOL;
    }
}

if ($missingfiles === []) {
    echo sprintf("[OK]   Release manifest\n       %d required files available.\n", count(CommerceMailReleaseManifest::required_files()));
} else {
    echo "[FAIL] Release manifest\n";
    foreach ($missingfiles as $missingfile) {
        echo '       Missing: ' . $missingfile . PHP_EOL;
    }
}

echo "\n------------------------------------------------------------\n";
echo sprintf(
    "OK=%d WARNINGS=%d ERRORS=%d MISSING_FILES=%d\n",
    $enginereport->count(CommerceMailCertificationFinding::OK),
    $enginereport->count(CommerceMailCertificationFinding::WARNING),
    $enginereport->count(CommerceMailCertificationFinding::ERROR),
    count($missingfiles)
);
echo 'STATUS: ' . ($certified ? 'CERTIFIED / FROZEN' : 'NOT CERTIFIED') . PHP_EOL;
echo "============================================================\n";

exit($certified ? 0 : 1);
