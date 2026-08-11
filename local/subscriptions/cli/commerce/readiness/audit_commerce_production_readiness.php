<?php

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\readiness\CommerceProductionReadinessAuditor;

[$options] = cli_get_params(
    ['strict' => false, 'json' => false, 'details' => false, 'help' => false],
    ['h' => 'help']
);

if ($options['help']) {
    cli_writeln('Read-only CampusFR Commerce production-readiness audit (phase 7.94I1).');
    cli_writeln('Options: --strict --json --details --help');
    exit(0);
}

$runtime = (string)get_config('local_subscriptions', 'commerce_runtime_mode');
if (!in_array($runtime, ['legacy', 'shadow', 'native'], true)) {
    $runtime = 'shadow';
}

$report = (new CommerceProductionReadinessAuditor(
    $DB,
    $CFG->dirroot,
    $CFG->dirroot . '/local/subscriptions'
))->audit([
    'branch' => '',
    'mode' => $runtime,
    'family' => 'all',
    'batch_size' => 100,
    // This compatibility CLI predates F8D. The canonical full F8E CLI
    // local/subscriptions/cli/commerce/prod_ready.php owns backup evidence.
    'include_backup_rollback' => false,
]);

if ($options['json']) {
    cli_writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('============================================================');
    cli_writeln('CampusFR Commerce - Production Readiness Compatibility Audit');
    cli_writeln('============================================================');
    cli_writeln('');

    $blocking = 0;
    $warnings = 0;
    foreach ($report['phases'] as $name => $phase) {
        $summary = $phase['summary'] ?? [];
        $phaseblocking = (int)($summary['blocking'] ?? 0);
        $phaseimportant = (int)($summary['important'] ?? 0);
        $phasecosmetic = (int)($summary['cosmetic'] ?? 0);
        $blocking += $phaseblocking;
        $warnings += $phaseimportant + $phasecosmetic;

        cli_writeln(
            str_pad($name . ':', 38)
            . (!empty($phase['passed']) ? 'PASS' : 'FAIL')
        );
        if ($options['details'] || empty($phase['passed'])) {
            cli_writeln(sprintf(
                '  blocking=%d important=%d cosmetic=%d',
                $phaseblocking,
                $phaseimportant,
                $phasecosmetic
            ));
        }
    }

    cli_writeln('');
    cli_writeln(str_repeat('-', 60));
    cli_writeln(str_pad('Runtime mode:', 38) . $runtime);
    cli_writeln(str_pad('Read-only audit:', 38) . 'YES');
    cli_writeln(str_pad('Backup evidence (F8D):', 38) . 'SKIPPED (use prod_ready.php)');
    cli_writeln(str_pad('Blocking issues:', 38) . $blocking);
    cli_writeln(str_pad('Warnings:', 38) . $warnings);
    cli_writeln(str_pad('READY FOR PRODUCTION:', 38) . ($report['ready'] ? 'YES' : 'NO'));
    cli_writeln('============================================================');
}

if ($options['strict'] && !$report['ready']) {
    exit(1);
}
