<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceBackupRollbackReadinessAuditor;

[$options, $unrecognised] = cli_get_params([
    'database-backup' => '',
    'code-backup' => '',
    'moodledata-backup' => '',
    'rollback-ref' => '',
    'max-age-hours' => 24,
    'minimum-free-gb' => 5,
    'json' => false,
    'help' => false,
], ['j' => 'json', 'h' => 'help']);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "7.95F8D — Backup & rollback readiness\n\n"
        . "--database-backup=/path/to/database.sql.gz\n"
        . "--code-backup=/path/to/code.tar.gz\n"
        . "--moodledata-backup=/path/to/moodledata.tar.gz\n"
        . "--rollback-ref=<git-tag-or-commit>\n"
        . "--max-age-hours=24\n--minimum-free-gb=5\n--json\n";
    exit(0);
}

$data = (new CommerceBackupRollbackReadinessAuditor(
    $CFG->dirroot,
    $CFG->dirroot . '/local/subscriptions'
))->audit([
    'database' => (string)$options['database-backup'],
    'code' => (string)$options['code-backup'],
    'moodledata' => (string)$options['moodledata-backup'],
], (string)$options['rollback-ref'], (int)$options['max-age-hours'], (int)$options['minimum-free-gb'])->to_array();

if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($data['certifiable'] ? 0 : 1);
}

echo "# 7.95F8D — Backup & rollback readiness\n\n";
echo '- Certifiable: ' . ($data['certifiable'] ? 'YES' : 'NO') . PHP_EOL;
echo '- Blocking issues: ' . $data['summary']['blocking'] . PHP_EOL;
echo '- Important issues: ' . $data['summary']['important'] . PHP_EOL . PHP_EOL;
echo "## Inventory\n\n";
foreach ($data['inventory'] as $key => $value) {
    echo '- **' . $key . '**: ' . (is_scalar($value) || $value === null
        ? var_export($value, true)
        : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . PHP_EOL;
}
echo "\n## Issues\n\n";
if (!$data['issues']) {
    echo "No issue detected.\n";
} else {
    foreach ($data['issues'] as $issue) {
        echo '- **' . strtoupper($issue['severity']) . ' / ' . $issue['code'] . '** — ' . $issue['message'] . PHP_EOL;
        if ($issue['context']) {
            echo '  - Context: `' . json_encode($issue['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '`' . PHP_EOL;
        }
    }
}
exit($data['certifiable'] ? 0 : 1);
