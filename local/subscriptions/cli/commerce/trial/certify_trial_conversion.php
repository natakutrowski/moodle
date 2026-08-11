<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\trial\CommerceTrialConversionCertificationService;

[$options] = cli_get_params(
    [
        'strict' => false,
        'json' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo "Certify the Legacy Trial to Native Commerce conversion.\n\n";
    echo "Options:\n";
    echo "  --strict   Return a non-zero exit code on warnings or errors.\n";
    echo "  --json     Output JSON.\n";
    exit(0);
}

$findings = (new CommerceTrialConversionCertificationService())->certify();
$warnings = count(array_filter(
    $findings,
    static fn(array $finding): bool => $finding['status'] === 'warning'
));
$errors = count(array_filter(
    $findings,
    static fn(array $finding): bool => $finding['status'] === 'error'
));

if (!empty($options['json'])) {
    echo json_encode([
        'findings' => $findings,
        'warnings' => $warnings,
        'errors' => $errors,
        'status' => $errors === 0 ? 'CERTIFIED' : 'FAILED',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "============================================================\n";
    echo "CampusFR Commerce — Trial Conversion Certification\n";
    echo "============================================================\n\n";

    foreach ($findings as $finding) {
        echo '[' . strtoupper(str_pad($finding['status'], 7)) . '] '
            . $finding['label'] . PHP_EOL;
        echo '          ' . $finding['detail'] . PHP_EOL;
    }

    echo "\n------------------------------------------------------------\n";
    echo 'WARNINGS=' . $warnings . ' ERRORS=' . $errors . PHP_EOL;
    echo 'STATUS: ' . ($errors === 0 ? 'CERTIFIED' : 'FAILED') . PHP_EOL;
}

if ($errors > 0 || (!empty($options['strict']) && $warnings > 0)) {
    exit(1);
}

exit(0);
