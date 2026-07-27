<?php

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\runtime\switching\CommerceRuntimePhaseAuditor;

[$options] = cli_get_params(['strict' => false, 'json' => false], []);
$report = (new CommerceRuntimePhaseAuditor())->audit();
if ($options['json']) {
    cli_writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('== Phase 7.94H1-H3 - Runtime Switch ==');
    foreach ($report['checks'] as $name => $ok) cli_writeln(str_pad($name . ':', 34) . ($ok ? 'OK' : 'ERROR'));
    cli_writeln('errors: ' . $report['errors']);
    cli_writeln('certified: ' . ($report['certified'] ? 'yes' : 'no'));
}
if ($options['strict'] && !$report['certified']) exit(1);
