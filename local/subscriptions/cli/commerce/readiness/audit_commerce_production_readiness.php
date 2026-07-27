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

$report = (new CommerceProductionReadinessAuditor())->audit();

if ($options['json']) {
    cli_writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('============================================================');
    cli_writeln('CampusFR Commerce - Production Readiness Audit (7.94I1)');
    cli_writeln('============================================================');
    cli_writeln('');

    foreach ($report['checks'] as $name => $check) {
        $label = strtoupper((string)$check['status']);
        cli_writeln(str_pad($name . ':', 38) . $label);
        if ($options['details'] || $check['status'] !== 'ok') {
            cli_writeln('  ' . $check['message']);
        }
    }

    cli_writeln('');
    cli_writeln(str_repeat('-', 60));
    cli_writeln(str_pad('Runtime mode:', 38) . $report['runtime_mode']);
    cli_writeln(str_pad('Read-only audit:', 38) . 'YES');
    cli_writeln(str_pad('Critical errors:', 38) . $report['errors']);
    cli_writeln(str_pad('Warnings:', 38) . $report['warnings']);
    cli_writeln(str_pad('READY FOR PRODUCTION:', 38) . ($report['ready'] ? 'YES' : 'NO'));
    cli_writeln('============================================================');
}

if ($options['strict'] && !$report['ready']) {
    exit(1);
}
