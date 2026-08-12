<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityOperationsCertificationService;

$report = (
    new CommerceCustomerIdentityOperationsCertificationService()
)->certify();

echo "======================================================================\n";
echo "CampusFR Commerce — Identity Operations M4.2 final certification\n";
echo "======================================================================\n";

$currentscope = '';
foreach ($report['checks'] as $check) {
    if ($currentscope !== $check['scope']) {
        $currentscope = $check['scope'];
        echo "\n[" . $currentscope . "]\n";
    }

    echo '  [' . ($check['ok'] ? 'OK' : 'ERROR') . '] '
        . $check['label'] . "\n";
    echo '       ' . $check['detail'] . "\n";
}

echo "\n----------------------------------------------------------------------\n";
echo 'ERRORS=' . $report['errors'] . "\n";
echo 'STATUS: ' . $report['status'] . "\n";

exit($report['errors'] === 0 ? 0 : 1);
