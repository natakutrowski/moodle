<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceGitReadinessAuditor;

[$options, $unrecognised] = cli_get_params([
    'branch' => '',
    'json' => false,
    'help' => false,
], ['b' => 'branch', 'j' => 'json', 'h' => 'help']);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "7.95F8A — Git & release audit\n\n--branch=NAME  Require an exact branch name.\n--json         Emit JSON.\n";
    exit(0);
}

$data = (new CommerceGitReadinessAuditor($CFG->dirroot))->audit((string)$options['branch'])->to_array();
if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($data['certifiable'] ? 0 : 1);
}

echo '# 7.95F8A — Git & release audit' . PHP_EOL . PHP_EOL;
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
