<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\storefront\certification\CommerceStorefrontDeltaLCertificationService;

$report = (new CommerceStorefrontDeltaLCertificationService())->certify();

echo "======================================================================\n";
echo "CampusFR Commerce — Delta L final certification\n";
echo "======================================================================\n\n";

$currentscope = null;
foreach ($report['checks'] as $check) {
    if ($currentscope !== $check['scope']) {
        $currentscope = $check['scope'];
        echo "\n[" . $currentscope . "]\n";
    }

    echo '  [' . ($check['ok'] ? 'OK' : 'ERROR') . '] ' . $check['label'] . "\n";
    echo '       ' . $check['detail'] . "\n";
}

echo "\n----------------------------------------------------------------------\n";
echo 'ERRORS=' . $report['errors'] . "\n";
echo 'STATUS: ' . $report['status'] . "\n";

exit($report['errors'] === 0 ? 0 : 1);
