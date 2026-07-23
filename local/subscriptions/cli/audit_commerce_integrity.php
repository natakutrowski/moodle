<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\integrity\CommerceIntegrityAuditor;

[$options] = cli_get_params(['since' => null, 'json' => false, 'strict' => false], ['h' => 'help']);
if (!empty($options['help'])) {
    echo "Audit recent Commerce evidence and idempotence.\n\n--since=YYYY-MM-DD  Baseline (defaults to commerce_runtime_audit_since)\n--json              JSON output\n--strict            Non-zero exit on BLOCKED\n";
    exit(0);
}
$raw = trim((string)($options['since'] ?? ''));
$baseline = $raw !== '' ? strtotime($raw . ' 00:00:00') : (int)get_config('local_subscriptions', 'commerce_runtime_audit_since');
if ($baseline <= 0) {
    cli_error('A baseline is required. Use --since=YYYY-MM-DD or configure commerce_runtime_audit_since.');
}
$report = (new CommerceIntegrityAuditor())->audit($baseline);
if (!empty($options['json'])) {
    echo json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo "Commerce Integrity Audit\n========================\n";
    foreach ($report->metrics() as $key => $value) { echo str_pad($key, 48) . ': ' . $value . PHP_EOL; }
    foreach ($report->issues() as $issue) { echo strtoupper($issue['severity']) . ' [' . $issue['code'] . '] ' . $issue['message'] . PHP_EOL; }
    echo "\nSTATUS: " . $report->status() . PHP_EOL;
}
exit(!empty($options['strict']) && $report->has_errors() ? 1 : 0);
