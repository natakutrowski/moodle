<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\CommerceIntegrityAuditReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'batch-size' => 100,
    'details' => false,
    'json' => false,
    'strict' => false,
    'report-file' => '',
], [
    'h' => 'help', 'f' => 'family', 'd' => 'details', 'j' => 'json', 's' => 'strict',
]);
if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo <<<HELP
Audit Legacy-to-Native Commerce migration integrity (read-only).

Options:
  --family=all|subscription|digital
  --batch-size=N       Read-only comparison batch size, 1..1000 (default 100).
  -d, --details        Display every anomaly.
  -j, --json           Emit the full JSON report to stdout.
  -s, --strict         Exit non-zero when integrity is not ready.
  --report-file=PATH   Atomically save the JSON report.
  -h, --help           Display this help.

Example:
  php local/subscriptions/cli/commerce/audit/audit_commerce_migration_integrity.php \
    --family=all --batch-size=100 --details --strict \
    --report-file=/tmp/campusfr-commerce-integrity.json

HELP;
    exit(0);
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$migrator = CommerceLegacyMigrationFactory::create_migrator();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
foreach ($families as $family) {
    $registry->get($family);
}
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$details = !empty($options['details']);
$strict = !empty($options['strict']);
$jsonoutput = !empty($options['json']);
$reportfile = trim((string)$options['report-file']);
$report = CommerceIntegrityAuditReport::start($families);

foreach ($families as $family) {
    $source = $registry->get($family);
    $report->set_legacy_total($family, $source->count());
    $afterid = 0;
    do {
        $ids = $source->get_ids($afterid, $batchsize);
        if ($ids === []) {
            break;
        }
        $summary = $migrator->migrate_batch($family, $ids, false);
        $report->record_results($family, $summary->get_results());
        $afterid = max($ids);
    } while (true);
}

// Native uniqueness and referential checks. All queries are read-only.
$report->set_native_integrity('duplicate_legacy_links', array_values($DB->get_records_sql(
    "SELECT MIN(id) AS id, legacyfamily, legacyid, COUNT(1) AS duplicatecount
       FROM {local_subscriptions_commerce_purchase}
      WHERE legacyfamily IN ('subscription', 'digital') AND legacyid > 0
   GROUP BY legacyfamily, legacyid
     HAVING COUNT(1) > 1"
)));
$report->set_native_integrity('duplicate_purchase_uuids', array_values($DB->get_records_sql(
    "SELECT MIN(id) AS id, purchaseuuid, COUNT(1) AS duplicatecount
       FROM {local_subscriptions_commerce_purchase}
      WHERE purchaseuuid IS NOT NULL AND purchaseuuid <> ''
   GROUP BY purchaseuuid
     HAVING COUNT(1) > 1"
)));
$report->set_native_integrity('duplicate_references', array_values($DB->get_records_sql(
    "SELECT MIN(id) AS id, reference, COUNT(1) AS duplicatecount
       FROM {local_subscriptions_commerce_purchase}
      WHERE reference IS NOT NULL AND reference <> ''
   GROUP BY reference
     HAVING COUNT(1) > 1"
)));
$report->set_native_integrity('orphan_legacy_links', array_values($DB->get_records_sql(
    "SELECT p.id, p.legacyfamily, p.legacyid, p.reference
       FROM {local_subscriptions_commerce_purchase} p
      WHERE (p.legacyfamily = 'subscription'
             AND NOT EXISTS (SELECT 1 FROM {user_subscription} us WHERE us.id = p.legacyid))
         OR (p.legacyfamily = 'digital'
             AND NOT EXISTS (SELECT 1 FROM {subscription_digital_payment_request} dp WHERE dp.id = p.legacyid))"
)));
$report->finish();
$data = $report->to_array();

if ($reportfile !== '') {
    try {
        $report->save($reportfile);
    } catch (\Throwable $exception) {
        cli_error('Unable to save the integrity report: ' . $exception->getMessage());
    }
}
if ($jsonoutput) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} else {
    cli_heading('CampusFR Commerce - Migration Integrity Audit (7.94I3)');
    foreach ($data['families'] as $family => $stats) {
        cli_writeln(sprintf(
            '%-12s Legacy=%d Checked=%d Healthy=%d Missing=%d Mismatch=%d Failed=%d',
            $family,
            $stats['legacy_total'],
            $stats['checked'],
            $stats['healthy'],
            $stats['missing_native'],
            $stats['mismatched'],
            $stats['failed']
        ));
    }
    cli_writeln('');
    cli_writeln('Duplicate groups:       ' . $data['summary']['duplicate_groups']);
    cli_writeln('Orphan Native purchases:' . ' ' . $data['summary']['orphan_native_purchases']);
    cli_writeln('Anomalies:              ' . $data['summary']['anomalies']);
    if ($details && $data['anomalies'] !== []) {
        cli_writeln('');
        cli_writeln('Legacy / Native anomalies:');
        foreach ($data['anomalies'] as $anomaly) {
            cli_writeln(sprintf(
                '  %s #%d: %s',
                $anomaly['legacyfamily'] ?? 'unknown',
                $anomaly['legacyid'] ?? 0,
                $anomaly['status'] ?? 'unknown'
            ));
            foreach (($anomaly['issues'] ?? []) as $issue) {
                cli_writeln('    [' . strtoupper((string)($issue['severity'] ?? 'error')) . '] '
                    . ($issue['code'] ?? 'unknown') . ': ' . ($issue['message'] ?? ''));
            }
        }
    }
    if ($reportfile !== '') {
        cli_writeln('Report:                 ' . $reportfile);
    }
    cli_writeln('Read-only audit:        YES');
    cli_writeln('INTEGRITY READY:        ' . ($report->is_ready() ? 'YES' : 'NO'));
}
exit($strict && !$report->is_ready() ? 1 : 0);
