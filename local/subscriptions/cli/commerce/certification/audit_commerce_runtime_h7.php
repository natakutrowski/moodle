<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\certification\CommerceRuntimeH7CertificationAuditor;

[$options] = cli_get_params(
    [
        'strict' => false,
        'json' => false,
        'mark-start' => false,
        'since' => null,
        'userid' => null,
        'help' => false,
    ],
    ['h' => 'help']
);

if ($options['help']) {
    cli_writeln('Certifies phase 7.94H7 from real Shadow executions.');
    cli_writeln('Options: --mark-start --since=TIMESTAMP --userid=ID --strict --json --help');
    exit(0);
}

if ($options['mark-start']) {
    $since = time();
    set_config('commerce_runtime_h7_since', $since, 'local_subscriptions');
    cli_writeln('H7 certification start recorded: ' . $since);
    exit(0);
}

$since = $options['since'] !== null ? max(0, (int)$options['since']) : null;
$userid = $options['userid'] !== null ? max(1, (int)$options['userid']) : null;
$report = (new CommerceRuntimeH7CertificationAuditor())->audit($since, $userid);

if ($options['json']) {
    cli_writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('== Phase 7.94H7 - Runtime Functional Certification ==');
    cli_writeln('');
    cli_writeln(str_pad('since:', 34) . $report['since']);
    cli_writeln(str_pad('userid:', 34) . ($report['userid'] ?? 'all'));
    cli_writeln('');
    foreach ($report['checks'] as $name => $ok) {
        cli_writeln(str_pad($name . ':', 34) . ($ok ? 'OK' : 'ERROR'));
    }
    cli_writeln('');
    foreach ($report['metrics'] as $name => $value) {
        cli_writeln(str_pad($name . ':', 34) . $value);
    }
    cli_writeln('');
    cli_writeln(str_pad('errors:', 34) . $report['errors']);
    cli_writeln(str_pad('certified:', 34) . ($report['certified'] ? 'yes' : 'no'));
}

if ($options['strict'] && !$report['certified']) {
    exit(1);
}
