<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\runtime\CommerceRuntimeAuditor;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
        'strict' => false,
        'since' => null,
        'save-baseline' => false,
    ],
    [
        'h' => 'help',
        'j' => 'json',
        's' => 'strict',
    ]
);

if ($unrecognized) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo "Commerce runtime audit\n\n";
    echo "Options:\n";
    echo "  --json                 JSON output\n";
    echo "  --strict               Exit non-zero when status is BLOCKED\n";
    echo "  --since=YYYY-MM-DD      Treat records on/after this date as recent\n";
    echo "  --since=TIMESTAMP       Same, using a Unix timestamp\n";
    echo "  --save-baseline         Persist the resolved --since value in plugin config\n";
    exit(0);
}

$baseline = null;
if ($options['since'] !== null && $options['since'] !== '') {
    $rawsince = trim((string)$options['since']);
    if (ctype_digit($rawsince)) {
        $baseline = (int)$rawsince;
    } else {
        $parsed = strtotime($rawsince . ' 00:00:00');
        if ($parsed === false) {
            cli_error('Invalid --since value. Use YYYY-MM-DD or a Unix timestamp.');
        }
        $baseline = $parsed;
    }
} else {
    $configured = get_config('local_subscriptions', 'commerce_runtime_audit_since');
    if ($configured !== false && (int)$configured > 0) {
        $baseline = (int)$configured;
    }
}

if ($options['save-baseline']) {
    if ($baseline === null) {
        cli_error('--save-baseline requires --since, or an existing configured baseline.');
    }
    set_config('commerce_runtime_audit_since', $baseline, 'local_subscriptions');
}

$report = (new CommerceRuntimeAuditor())->audit($baseline);

if ($options['json']) {
    echo json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "Commerce Runtime Audit\n";
    echo "======================\n";
    foreach ($report->get_checks() as $key => $value) {
        echo str_pad($key, 58) . ': ' . (is_bool($value) ? ($value ? 'ON' : 'OFF') : $value) . PHP_EOL;
    }
    foreach ($report->get_issues() as $issue) {
        echo strtoupper($issue['severity']) . ' [' . $issue['code'] . '] ' . $issue['message'] . PHP_EOL;
    }
    echo "\nSTATUS: " . $report->get_status() . PHP_EOL;
}

if ($options['strict'] && $report->get_status() === 'BLOCKED') {
    exit(1);
}
exit(0);