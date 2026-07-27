<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackReport;
use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;

[$options] = cli_get_params([
    'execute' => false,
    'confirm-runtime-rollback' => null,
    'report-file' => null,
    'help' => false,
], [
    'h' => 'help',
]);

if ($options['help']) {
    cli_writeln(<<<'HELP'
CampusFR Commerce - Guarded Runtime Rollback (7.94I4)

By default, this command is read-only and previews the rollback.
It never deletes or rewrites Legacy or Native Commerce data.

Options:
  --execute
      Apply the rollback configuration.

  --confirm-runtime-rollback=legacy
      Mandatory exact confirmation when --execute is used.

  --report-file=PATH
      Write an atomic JSON report.

  -h, --help
      Show this help.

Dry-run example:
  php rollback_commerce_runtime.php \
    --report-file=/tmp/campusfr-runtime-rollback-preview.json

Execution example:
  php rollback_commerce_runtime.php \
    --execute \
    --confirm-runtime-rollback=legacy \
    --report-file=/tmp/campusfr-runtime-rollback-execute.json
HELP);
    exit(0);
}

$execute = (bool) $options['execute'];
$confirmation = (string) ($options['confirm-runtime-rollback'] ?? '');
$reportfile = trim((string) ($options['report-file'] ?? ''));

if ($execute && $confirmation !== 'legacy') {
    cli_error('Execution requires --confirm-runtime-rollback=legacy.');
}
if (!$execute && $confirmation !== '') {
    cli_error('--confirm-runtime-rollback is only valid with --execute.');
}

$service = new CommerceRuntimeRollbackService();
$mode = $execute ? 'execute' : 'dry_run';

cli_writeln('== CampusFR Commerce - Guarded Runtime Rollback (7.94I4) ==');
cli_writeln('Mode:                    ' . strtoupper(str_replace('_', '-', $mode)));
cli_writeln('Commerce data changes:   NO');

try {
    $result = $execute ? $service->execute() : $service->inspect();
    $status = $execute ? 'completed' : 'preview';
    $report = CommerceRuntimeRollbackReport::build($mode, $status, $result);

    $before = $result['before'];
    $after = $execute ? $result['after'] : $result['target'];

    cli_writeln('Before runtime mode:     ' . $before['runtime_mode']);
    cli_writeln('Before native fallback:  ' . ($before['native_fallback_enabled'] ? 'YES' : 'NO'));
    cli_writeln('Before shadow:           ' . ($before['shadow_enabled'] ? 'YES' : 'NO'));
    cli_writeln('Target runtime mode:     ' . $after['runtime_mode']);
    cli_writeln('Target native fallback:  ' . ($after['native_fallback_enabled'] ? 'YES' : 'NO'));
    cli_writeln('Target shadow:           ' . ($after['shadow_enabled'] ? 'YES' : 'NO'));

    if ($execute) {
        cli_writeln('Verification:            ' . ($result['verified'] ? 'OK' : 'FAILED'));
    }

    if ($reportfile !== '') {
        CommerceRuntimeRollbackReport::write_atomic($reportfile, $report);
        cli_writeln('Report:                  ' . $reportfile);
    }

    if ($execute) {
        cli_writeln('[OK] Commerce runtime rollback completed and verified.');
    } else {
        cli_writeln('[OK] Rollback preview completed. No configuration was changed.');
    }
} catch (\Throwable $exception) {
    $report = CommerceRuntimeRollbackReport::build($mode, 'failed', [], $exception->getMessage());
    if ($reportfile !== '') {
        try {
            CommerceRuntimeRollbackReport::write_atomic($reportfile, $report);
        } catch (\Throwable $reportexception) {
            cli_writeln('[WARNING] Unable to write rollback report: ' . $reportexception->getMessage());
        }
    }
    cli_error('Commerce runtime rollback failed: ' . $exception->getMessage());
}
