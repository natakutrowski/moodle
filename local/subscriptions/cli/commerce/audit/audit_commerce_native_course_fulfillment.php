<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\fulfillment\native\audit\CommerceNativeCourseFulfillmentAuditor;

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'json' => false, 'strict' => false],
    ['h' => 'help']
);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo "Certify phases 7.94F2-F3 Native course fulfillment.\n\n";
    echo "Options:\n--json    JSON output\n--strict  Exit non-zero when not certified\n";
    exit(0);
}

$report = (new CommerceNativeCourseFulfillmentAuditor(__DIR__ . '/..'))->audit();

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "== Phase 7.94F2-F3 - Native Course Fulfillment ==\n";
    foreach ($report['checks'] as $name => $passed) {
        printf("%-14s %s\n", $name . ':', $passed ? 'OK' : 'ERROR');
    }
    echo 'errors:        ' . count($report['errors']) . PHP_EOL;
    foreach ($report['errors'] as $error) {
        echo ' - ' . $error . PHP_EOL;
    }
    echo 'certified:     ' . ($report['certified'] ? 'yes' : 'no') . PHP_EOL;
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
