<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceProductionCertificationAuditor;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'batch-size' => 100,
    'ack-phpunit-passed' => false,
    'ack-backup-procedure' => false,
    'ack-maintenance-procedure' => false,
    'ack-rollback-procedure' => false,
    'ack-smoke-test-procedure' => false,
    'git-branch' => '',
    'git-commit' => '',
    'operator' => '',
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
Build the final read-only CampusFR Commerce production certification (7.94I6).

Options:
  --family=all|subscription|digital
  --batch-size=N                 Integrity comparison batch size, 1..1000.
  --ack-phpunit-passed           Attest that the final plugin PHPUnit suite passed.
  --ack-backup-procedure         Confirm backup procedure is documented and assigned.
  --ack-maintenance-procedure    Confirm maintenance procedure is documented.
  --ack-rollback-procedure       Confirm rollback procedure and operator are documented.
  --ack-smoke-test-procedure     Confirm post-switch smoke tests are documented.
  --git-branch=NAME              Branch containing the frozen release commit.
  --git-commit=SHA               Full 40-character immutable release commit SHA-1.
  --operator=NAME                Operator responsible for the certification.
  -j, --json                     Emit the full JSON report to stdout.
  -s, --strict                   Exit non-zero when certification is blocked.
  --report-file=PATH             Atomically save the JSON report.
  -h, --help                     Display this help.

This command is read-only. It does not execute PHPUnit, change Runtime settings,
modify Commerce data, perform backups or deploy code.

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
$releaseidentity = [
    'git_branch' => trim((string)$options['git-branch']),
    'git_commit' => strtolower(trim((string)$options['git-commit'])),
    'operator' => trim((string)$options['operator']),
];

$report = (new CommerceProductionCertificationAuditor())->audit(
    $families,
    $batchsize,
    $acknowledgements,
    !empty($options['ack-phpunit-passed']),
    $releaseidentity
);
$data = $report->to_array();
$reportfile = trim((string)$options['report-file']);
if ($reportfile !== '') {
    try {
        $report->save($reportfile);
    } catch (\Throwable $exception) {
        cli_error('Unable to save the production certification report: ' . $exception->getMessage());
    }
}

if (!empty($options['json'])) {
    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
} else {
    cli_heading('CampusFR Commerce - Production Certification (7.94I6)');
    cli_writeln('Certification ID:              ' . $data['certification_id']);
    cli_writeln('Git branch:                    ' . ($data['metadata']['git_branch'] ?: '(missing)'));
    cli_writeln('Git commit:                    ' . ($data['metadata']['git_commit'] ?: '(missing)'));
    cli_writeln('Operator:                      ' . ($data['metadata']['operator'] ?: '(missing)'));
    cli_writeln('Runtime mode:                  ' . $data['metadata']['runtime_mode']);
    cli_writeln('');
    cli_writeln('Certification checks:');
    foreach ($data['checks'] as $name => $check) {
        cli_writeln(sprintf('  %-38s %s', $name . ':', strtoupper((string)$check['status'])));
        cli_writeln('    ' . $check['message']);
    }
    cli_writeln('');
    if ($reportfile !== '') {
        cli_writeln('Report:                        ' . $reportfile);
    }
    cli_writeln('Read-only certification:       YES');
    cli_writeln('Failed checks:                 ' . $data['summary']['failed']);
    cli_writeln('COMMERCE CERTIFIED FOR PROD:   ' . ($report->is_certified() ? 'YES' : 'NO'));
}

exit(!empty($options['strict']) && !$report->is_certified() ? 1 : 0);
