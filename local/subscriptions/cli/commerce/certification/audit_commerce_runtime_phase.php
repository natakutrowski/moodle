<?php

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeFinalPhaseAuditor;

[$options] = cli_get_params(
    ['strict' => false, 'json' => false, 'help' => false],
    ['h' => 'help']
);

if ($options['help']) {
    cli_writeln('Certifies phase 7.94H Commerce Runtime Switch.');
    cli_writeln('Options: --strict --json --help');
    exit(0);
}

$report = (new CommerceRuntimeFinalPhaseAuditor())->audit();
if ($options['json']) {
    cli_writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('== Phase 7.94H - Commerce Runtime Switch ==');
    cli_writeln('');
    foreach ($report['checks'] as $name => $ok) {
        cli_writeln(str_pad($name . ':', 30) . ($ok ? 'OK' : 'ERROR'));
    }
    cli_writeln(str_pad('scenarios:', 30) . $report['scenario_count']);
    cli_writeln(str_pad('errors:', 30) . $report['errors']);
    cli_writeln(str_pad('certified:', 30) . ($report['certified'] ? 'yes' : 'no'));
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
