<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceNativeReadinessAuditor;

[$options, $unrecognised] = cli_get_params(['mode' => 'native', 'json' => false, 'help' => false], ['m' => 'mode', 'j' => 'json', 'h' => 'help']);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "7.95F8B — Native production readiness\n\n--mode=native|shadow|legacy  Expected runtime mode.\n--json                       Emit JSON.\n";
    exit(0);
}

$data = (new CommerceNativeReadinessAuditor($DB))->audit((string)$options['mode'])->to_array();
if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($data['certifiable'] ? 0 : 1);
}

echo '# 7.95F8B — Native production readiness' . PHP_EOL . PHP_EOL;
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
    }
}
exit($data['certifiable'] ? 0 : 1);
