<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\deployment\CommerceDeploymentChecklistAuditor;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'batch-size' => 100,
    'ack-backup-procedure' => false,
    'ack-maintenance-procedure' => false,
    'ack-rollback-procedure' => false,
    'ack-smoke-test-procedure' => false,
    'json' => false,
    'strict' => false,
    'report-file' => '',
], [
    'h' => 'help', 'f' => 'family', 'j' => 'json', 's' => 'strict',
]);
if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo <<<HELP
Build the read-only CampusFR Commerce deployment checklist (7.94I5).

Options:
  --family=all|subscription|digital
  --batch-size=N                 Integrity comparison batch size, 1..1000.
  --ack-backup-procedure         Confirm backup procedure is documented and assigned.
  --ack-maintenance-procedure    Confirm maintenance procedure is documented.
  --ack-rollback-procedure       Confirm rollback procedure and operator are documented.
  --ack-smoke-test-procedure     Confirm post-switch smoke tests are documented.
  -j, --json                     Emit the full JSON report to stdout.
  -s, --strict                   Exit non-zero when the checklist is blocked.
  --report-file=PATH             Atomically save the JSON report.
  -h, --help                     Display this help.

This command is read-only. Acknowledgements certify prepared procedures only;
they do not claim that a production backup or migration has already been executed.

HELP;
    exit(0);
}

$registry = CommerceLegacyMigrationFactory::create_source_registry();
$familyoption = strtolower(trim((string)$options['family']));
$families = $familyoption === 'all' ? $registry->get_families() : [$familyoption];
foreach ($families as $family) {
    $registry->get($family);
}
$batchsize = max(1, min(1000, (int)$options['batch-size']));
$acknowledgements = [
    'backup_procedure' => !empty($options['ack-backup-procedure']),
    'maintenance_procedure' => !empty($options['ack-maintenance-procedure']),
    'rollback_procedure' => !empty($options['ack-rollback-procedure']),
    'smoke_test_procedure' => !empty($options['ack-smoke-test-procedure']),
];

$report = (new CommerceDeploymentChecklistAuditor())->audit($families, $batchsize, $acknowledgements);
$data = $report->to_array();
$reportfile = trim((string)$options['report-file']);
if ($reportfile !== '') {
    try {
        $report->save($reportfile);
    } catch (\Throwable $exception) {
        cli_error('Unable to save the deployment checklist report: ' . $exception->getMessage());
    }
}

if (!empty($options['json'])) {
    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
} else {
    cli_heading('CampusFR Commerce - Deployment Checklist (7.94I5)');
    cli_writeln('Automated checks:');
    foreach ($data['automated_checks'] as $name => $check) {
        cli_writeln(sprintf('  %-30s %s', $name . ':', strtoupper((string)$check['status'])));
        cli_writeln('    ' . $check['message']);
    }
    cli_writeln('');
    cli_writeln('Prepared operator procedures:');
    foreach ($data['operator_acknowledgements'] as $name => $acknowledgement) {
        cli_writeln(sprintf('  %-30s %s', $name . ':', strtoupper((string)$acknowledgement['status'])));
        cli_writeln('    ' . $acknowledgement['message']);
    }
    cli_writeln('');
    if ($reportfile !== '') {
        cli_writeln('Report:                         ' . $reportfile);
    }
    cli_writeln('Read-only checklist:            YES');
    cli_writeln('DEPLOYMENT CHECKLIST READY:     ' . ($report->is_ready() ? 'YES' : 'NO'));
}

exit(!empty($options['strict']) && !$report->is_ready() ? 1 : 0);
