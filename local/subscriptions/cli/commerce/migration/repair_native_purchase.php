<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceTargetedNativeRepairService;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => '',
    'legacy-id' => 0,
    'execute' => false,
    'confirm-targeted-repair' => '',
    'report-file' => '',
], [
    'h' => 'help',
    'f' => 'family',
    'i' => 'legacy-id',
    'e' => 'execute',
]);
if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo <<<HELP
Inspect or repair one divergent Native Commerce purchase.

The default mode is read-only. Execution is deliberately restricted to one
Legacy identifier and requires an exact confirmation token.

Options:
  --family=subscription|digital
  --legacy-id=ID
  -e, --execute
  --confirm-targeted-repair=FAMILY:ID
  --report-file=PATH
  -h, --help

Dry-run:
  php local/subscriptions/cli/commerce/migration/repair_native_purchase.php \
    --family=subscription --legacy-id=309

Execute after reviewing the dry-run:
  php local/subscriptions/cli/commerce/migration/repair_native_purchase.php \
    --family=subscription --legacy-id=309 --execute \
    --confirm-targeted-repair=subscription:309 \
    --report-file=/tmp/campusfr-repair-subscription-309.json

Safety policy:
  - never creates a missing Native purchase;
  - never repairs more than one purchase;
  - never changes immutable identity fields;
  - never repairs child aggregate differences;
  - verifies Legacy/Native equality before committing;
  - rolls back automatically when verification fails.

HELP;
    exit(0);
}

$family = strtolower(trim((string)$options['family']));
$legacyid = max(0, (int)$options['legacy-id']);
$execute = !empty($options['execute']);
$confirmation = trim((string)$options['confirm-targeted-repair']);
$reportfile = trim((string)$options['report-file']);

if (!in_array($family, ['subscription', 'digital'], true)) {
    cli_error('--family must be subscription or digital.');
}
if ($legacyid <= 0) {
    cli_error('--legacy-id must be a positive integer.');
}
if ($execute && $confirmation !== $family . ':' . $legacyid) {
    cli_error('--execute requires --confirm-targeted-repair=' . $family . ':' . $legacyid);
}

$migrator = CommerceLegacyMigrationFactory::create_migrator();
$service = new CommerceTargetedNativeRepairService($DB, $migrator);
$inspection = $service->inspect($family, $legacyid);
$report = [
    'format_version' => 1,
    'mode' => $execute ? 'execute' : 'dry_run',
    'started_at' => time(),
    'family' => $family,
    'legacyid' => $legacyid,
    'inspection' => $inspection,
    'updated_native_purchase_id' => null,
    'verification' => null,
    'status' => 'inspected',
];

cli_heading('CampusFR Commerce - Targeted Native Repair (7.94I3A)');
cli_writeln('Target: ' . $family . ' #' . $legacyid);
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));
cli_writeln('Repairable: ' . (!empty($inspection['repairable']) ? 'YES' : 'NO'));
cli_writeln($inspection['message'] ?? '');

if (!empty($inspection['changes'])) {
    cli_writeln('Changes:');
    foreach ($inspection['changes'] as $field => $change) {
        cli_writeln('  ' . $field);
        cli_writeln('    before: ' . json_encode($change['before'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        cli_writeln('    after:  ' . json_encode($change['after'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

$exitcode = 0;
if ($execute) {
    if (empty($inspection['repairable'])) {
        $report['status'] = 'refused';
        $exitcode = 1;
    } else {
        $transaction = $DB->start_delegated_transaction();
        try {
            $report['updated_native_purchase_id'] = $service->apply($inspection);
            $verification = $service->inspect($family, $legacyid);
            $report['verification'] = $verification;
            if (($verification['status'] ?? '') !== 'already_present') {
                throw new \RuntimeException('Post-repair Legacy/Native verification failed.');
            }
            $transaction->allow_commit();
            $report['status'] = 'repaired_and_verified';
            cli_writeln('[OK] Repair committed and verified.');
        } catch (\Throwable $exception) {
            try {
                $transaction->rollback($exception);
            } catch (\Throwable $rolledback) {
                $report['status'] = 'rolled_back';
                $report['error'] = $rolledback->getMessage();
            }
            cli_writeln('[ERROR] Repair rolled back: ' . ($report['error'] ?? $exception->getMessage()));
            $exitcode = 1;
        }
    }
} else if (!empty($inspection['repairable'])) {
    cli_writeln('[OK] Dry-run complete. Review the diff before using --execute.');
} else {
    cli_writeln('[INFO] No supported repair can be applied.');
}

$report['finished_at'] = time();
if ($reportfile !== '') {
    $directory = dirname($reportfile);
    if (!is_dir($directory) || !is_writable($directory)) {
        cli_error('Report directory is not writable: ' . $directory);
    }
    $temporary = $reportfile . '.tmp.' . getmypid();
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $reportfile)) {
        @unlink($temporary);
        cli_error('Unable to write report atomically: ' . $reportfile);
    }
    cli_writeln('Report: ' . $reportfile);
}
exit($exitcode);
