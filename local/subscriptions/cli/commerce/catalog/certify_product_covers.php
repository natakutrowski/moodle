<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\cover\CommerceProductCoverCertificationService;

[$options] = cli_get_params([
    'strict' => false,
    'json' => false,
    'help' => false,
], [
    'h' => 'help',
]);

if (!empty($options['help'])) {
    echo "Certify Commerce product visuals.\n\n";
    echo "Options:\n";
    echo "  --strict  Return exit code 1 when an error is found.\n";
    echo "  --json    Output JSON.\n";
    exit(0);
}

$service = new CommerceProductCoverCertificationService();
$findings = $service->certify();
$haserrors = $service->has_errors($findings);

if (!empty($options['json'])) {
    echo json_encode([
        'status' => $haserrors ? 'FAILED' : 'CERTIFIED',
        'findings' => $findings,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "============================================================\n";
    echo "CampusFR Commerce — Product Visuals Certification\n";
    echo "============================================================\n\n";
    foreach ($findings as $finding) {
        $marker = $finding['status'] === 'ok' ? 'OK' : 'ERROR';
        printf("[%-5s] %s\n        %s\n", $marker, $finding['label'], $finding['detail']);
    }
    echo "\n------------------------------------------------------------\n";
    echo 'STATUS: ' . ($haserrors ? 'FAILED' : 'CERTIFIED') . PHP_EOL;
}

exit(!empty($options['strict']) && $haserrors ? 1 : 0);
