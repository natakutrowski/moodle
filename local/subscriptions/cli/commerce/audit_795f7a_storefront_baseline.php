<?php

/**
 * Read-only 7.95F7A Commerce storefront baseline audit.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceStorefrontBaselineAuditor;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
        'output' => null,
    ],
    [
        'h' => 'help',
        'j' => 'json',
        'o' => 'output',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unknown options:\n  " . $unrecognized);
}

if ($options['help']) {
    echo <<<HELP
7.95F7A Commerce storefront baseline audit (read-only).

Options:
  -h, --help            Show this help.
  -j, --json            Print the complete report as JSON.
  -o, --output=FILE     Also write the report to FILE.
                         JSON is written when --json is present; otherwise Markdown.

Examples:
  php local/subscriptions/cli/commerce/audit_795f7a_storefront_baseline.php
  php local/subscriptions/cli/commerce/audit_795f7a_storefront_baseline.php --json
  php local/subscriptions/cli/commerce/audit_795f7a_storefront_baseline.php --output=/tmp/f7a-report.md

The audit performs no database writes.
HELP;
    exit(0);
}

$report = (new CommerceStorefrontBaselineAuditor($DB))->audit();
$data = $report->to_array();

if ($options['json']) {
    $rendered = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($rendered === false) {
        cli_error('Unable to encode the F7A report as JSON.');
    }
    $rendered .= PHP_EOL;
} else {
    $lines = [
        '# Commerce 7.95F7A — Storefront baseline',
        '',
        '- Generated: ' . userdate((int)$data['generatedat']),
        '- Baseline certifiable: ' . ($data['certifiablebaseline'] ? 'YES' : 'NO'),
        '- Blocking issues: ' . $data['summary']['blocking'],
        '- Important issues: ' . $data['summary']['important'],
        '- Cosmetic issues: ' . $data['summary']['cosmetic'],
        '',
        '## Checks',
        '',
    ];
    foreach ($data['checks'] as $name => $passed) {
        $lines[] = '- ' . ($passed ? '[PASS] ' : '[FAIL] ') . $name;
    }
    $lines[] = '';
    $lines[] = '## Inventory';
    $lines[] = '';
    foreach ($data['inventory'] as $name => $value) {
        $display = is_array($value)
            ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : var_export($value, true);
        $lines[] = '- **' . $name . '**: ' . $display;
    }
    $lines[] = '';
    $lines[] = '## Issues';
    $lines[] = '';
    if ($data['issues'] === []) {
        $lines[] = 'No issue detected.';
    } else {
        foreach ($data['issues'] as $issue) {
            $lines[] = '- **' . strtoupper($issue['severity']) . ' / ' . $issue['code'] . '** — ' . $issue['message'];
            if ($issue['context'] !== []) {
                $lines[] = '  - Context: `' . json_encode($issue['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '`';
            }
        }
    }
    $rendered = implode(PHP_EOL, $lines) . PHP_EOL;
}

echo $rendered;

if ($options['output'] !== null && trim((string)$options['output']) !== '') {
    $path = (string)$options['output'];
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        cli_error('Output directory is not writable: ' . $directory);
    }
    if (file_put_contents($path, $rendered) === false) {
        cli_error('Unable to write report: ' . $path);
    }
    cli_writeln('Report written to: ' . $path);
}

exit($report->has_blocking_issues() ? 1 : 0);
