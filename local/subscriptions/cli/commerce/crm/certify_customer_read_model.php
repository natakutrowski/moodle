<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\customer\certification\CommerceCustomerCrmCertificationService;

[$options] = cli_get_params(['strict' => false, 'json' => false], ['h' => 'help']);
$strict = !empty($options['strict']);
$report = (new CommerceCustomerCrmCertificationService($DB))->certify();

if (!empty($options['json'])) {
    echo json_encode([
        'findings' => $report->findings,
        'errors' => $report->error_count(),
        'warnings' => $report->warning_count(),
        'certified' => $report->is_certified($strict),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($report->is_certified($strict) ? 0 : 1);
}

echo "============================================================\n";
echo "CampusFR Commerce — CRM Customer Read Model Certification\n";
echo "============================================================\n\n";
foreach ($report->findings as $finding) {
    printf("[%-5s] %s\n        %s\n", $finding['status'], $finding['label'], $finding['detail']);
}
echo "\n------------------------------------------------------------\n";
printf("WARNINGS=%d ERRORS=%d\n", $report->warning_count(), $report->error_count());
echo 'STATUS: ' . ($report->is_certified($strict) ? 'CERTIFIED' : 'NOT CERTIFIED') . PHP_EOL;
exit($report->is_certified($strict) ? 0 : 1);
