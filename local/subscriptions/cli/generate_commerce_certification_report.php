<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceCertificationService;

[$options] = cli_get_params(['since' => null, 'output' => null, 'json' => false, 'strict' => false], ['h' => 'help']);
if (!empty($options['help'])) {
    echo "Generate the Commerce production certification report.\n\n--since=YYYY-MM-DD  Certification baseline\n--output=/path/file  Write Markdown report\n--json              JSON output instead of Markdown\n--strict            Non-zero exit if BLOCKED\n";
    exit(0);
}
$raw = trim((string)($options['since'] ?? ''));
$baseline = $raw !== '' ? strtotime($raw . ' 00:00:00') : (int)get_config('local_subscriptions', 'commerce_runtime_audit_since');
if ($baseline <= 0) { cli_error('A baseline is required. Use --since=YYYY-MM-DD or configure commerce_runtime_audit_since.'); }
$report = (new CommerceCertificationService())->certify($baseline);
$content = !empty($options['json'])
    ? json_encode($report->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
    : $report->to_markdown();
$output = trim((string)($options['output'] ?? ''));
if ($output !== '') {
    if (file_put_contents($output, $content) === false) { cli_error('Unable to write report: ' . $output); }
    echo 'Report written to ' . $output . PHP_EOL;
    echo 'STATUS: ' . $report->global_status() . PHP_EOL;
} else { echo $content; }
exit(!empty($options['strict']) && $report->global_status() === 'BLOCKED' ? 1 : 0);
