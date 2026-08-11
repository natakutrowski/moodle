<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\professional\certification\CommerceCustomerExperienceCertificationService;

$report = (new CommerceCustomerExperienceCertificationService())->certify();

echo "============================================================\n";
echo "CampusFR Commerce — Customer Experience Certification\n";
echo "============================================================\n\n";
foreach ($report['checks'] as $check) {
    echo '[' . ($check['ok'] ? 'OK' : 'ERROR') . '] ' . $check['label'] . "\n";
    echo '     ' . $check['detail'] . "\n";
}
echo "\n------------------------------------------------------------\n";
echo 'ERRORS=' . $report['errors'] . "\n";
echo 'STATUS: ' . $report['status'] . "\n";
exit($report['errors'] === 0 ? 0 : 1);
