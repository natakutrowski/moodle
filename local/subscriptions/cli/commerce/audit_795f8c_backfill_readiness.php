<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceBackfillReadinessAuditor;

[$options, $unrecognised] = cli_get_params([
    'family' => 'all',
    'batch-size' => 100,
    'json' => false,
    'help' => false,
], ['f' => 'family', 's' => 'batch-size', 'j' => 'json', 'h' => 'help']);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "7.95F8C — Backfill readiness\n\n--family=all|subscription|digital\n--batch-size=100\n--json\n\nThis command is read-only. Missing Native rows are reported as IMPORTANT because they are expected before the production backfill.\n";
    exit(0);
}

$data = (new CommerceBackfillReadinessAuditor($DB))->audit((string)$options['family'], (int)$options['batch-size'])->to_array();
if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($data['certifiable'] ? 0 : 1);
}

echo '# 7.95F8C — Backfill readiness' . PHP_EOL . PHP_EOL;
echo '- Certifiable: ' . ($data['certifiable'] ? 'YES' : 'NO') . PHP_EOL;
echo '- Blocking issues: ' . $data['summary']['blocking'] . PHP_EOL;
echo '- Important issues: ' . $data['summary']['important'] . PHP_EOL . PHP_EOL;
echo "## Inventory\n\n";
foreach ($data['inventory'] as $key => $value) {
    echo '- **' . $key . '**: ' . (is_scalar($value) || $value === null ? var_export($value, true) : json_encode($value, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}
echo "\n## Issues\n\n";
if (!$data['issues']) {
    echo "No issue detected.\n";
} else {
    foreach ($data['issues'] as $issue) {
        echo '- **' . strtoupper($issue['severity']) . ' / ' . $issue['code'] . '** — ' . $issue['message'] . PHP_EOL;
        if ($issue['context']) {
            echo '  - Context: `' . json_encode($issue['context'], JSON_UNESCAPED_UNICODE) . '`' . PHP_EOL;
        }
    }
}
exit($data['certifiable'] ? 0 : 1);
