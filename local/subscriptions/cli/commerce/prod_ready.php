<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceProductionReadinessAuditor;

[$options, $unrecognised] = cli_get_params([
    'branch' => 'commerce-7.95',
    'mode' => 'native',
    'family' => 'all',
    'batch-size' => 100,
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
    echo "Commerce 7.95F8E — Global production readiness\n\n"
        . "--branch=commerce-7.95 --mode=native --family=all --batch-size=100\n"
        . "--database-backup=/path/to/database.sql.gz\n"
        . "--code-backup=/path/to/code.tar.gz\n"
        . "--moodledata-backup=/path/to/moodledata.tar.gz\n"
        . "--rollback-ref=<git-tag-or-commit>\n"
        . "--max-age-hours=24 --minimum-free-gb=5 --json\n";
    exit(0);
}

$data = (new CommerceProductionReadinessAuditor(
    $DB,
    $CFG->dirroot,
    $CFG->dirroot . '/local/subscriptions'
))->audit([
    'branch' => (string)$options['branch'],
    'mode' => (string)$options['mode'],
    'family' => (string)$options['family'],
    'batch_size' => (int)$options['batch-size'],
    'database_backup' => (string)$options['database-backup'],
    'code_backup' => (string)$options['code-backup'],
    'moodledata_backup' => (string)$options['moodledata-backup'],
    'rollback_ref' => (string)$options['rollback-ref'],
    'max_backup_age_hours' => (int)$options['max-age-hours'],
    'minimum_free_gb' => (int)$options['minimum-free-gb'],
]);

if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($data['ready'] ? 0 : 1);
}

echo "========================================\n";
echo "Commerce 7.95 — Production Readiness\n";
echo "========================================\n\n";
foreach ($data['environment'] as $key => $value) {
    echo sprintf("%-20s %s\n", $key . ':', is_array($value) ? json_encode($value) : (string)($value ?? 'n/a'));
}
echo PHP_EOL;
foreach ($data['phases'] as $label => $phase) {
    $summary = $phase['summary'];
    echo sprintf(
        "%-28s %s  blocking=%d important=%d cosmetic=%d\n",
        $label,
        $phase['passed'] ? 'PASS' : 'FAIL',
        (int)($summary['blocking'] ?? 0),
        (int)($summary['important'] ?? 0),
        (int)($summary['cosmetic'] ?? 0)
    );
}
echo "\n========================================\n";
echo $data['ready'] ? "READY FOR PRODUCTION\n" : "NOT READY FOR PRODUCTION\n";
echo "========================================\n";
exit($data['ready'] ? 0 : 1);
