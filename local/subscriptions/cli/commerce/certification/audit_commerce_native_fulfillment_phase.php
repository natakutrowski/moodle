<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\fulfillment\native\audit\CommerceNativeFulfillmentPhaseAuditor;

[$options] = cli_get_params(
    ['strict' => false, 'json' => false, 'help' => false],
    ['h' => 'help']
);

if ($options['help']) {
    echo "Audit the complete 7.94F Native Fulfillment phase.\n\n";
    echo "--strict  Exit non-zero when not certified\n";
    echo "--json    JSON output\n";
    exit(0);
}

$result = (new CommerceNativeFulfillmentPhaseAuditor())->run();

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94F - Native Fulfillment Engine ==\n\n";
    foreach ($result['checks'] as $key => $ok) {
        printf("%-16s %s\n", $key . ':', $ok ? 'OK' : 'FAIL');
    }
    printf("%-16s %d\n", 'errors:', count($result['errors']));
    foreach ($result['errors'] as $error) {
        echo ' - ' . $error . PHP_EOL;
    }
    printf("%-16s %s\n", 'certified:', $result['certified'] ? 'yes' : 'no');
}

exit($options['strict'] && !$result['certified'] ? 1 : 0);
