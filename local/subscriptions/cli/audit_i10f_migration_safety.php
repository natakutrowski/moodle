<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\rollout\CommerceMigrationSafetyInspector;

[$options] = cli_get_params(
    [
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

$report = (new CommerceMigrationSafetyInspector())->inspect(__DIR__ . '/..');
$reportdata = $report->to_array();

cli_writeln('== I10F PROD migration safety ==');

foreach ($reportdata as $key => $value) {
    if ($key === 'notes') {
        continue;
    }

    cli_writeln(sprintf(
        '  %-30s %s',
        $key,
        $value ? 'yes' : 'no'
    ));
}

foreach ($reportdata['notes'] as $note) {
    cli_writeln('  [INFO] ' . $note);
}

if (!$report->is_safe_for_preprod() && !empty($options['strict'])) {
    cli_error('Migration safety requirements are not met.');
}

cli_writeln(
    $report->is_safe_for_preprod()
        ? '[OK] Upgrade and rollback compatibility protections are present.'
        : '[WARN] Migration safety requirements need review.'
);
