<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\rollout\CommerceRuntimeWriteInventory;

[$options] = cli_get_params(
    [
        'strict' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
    ]
);

$inventory = new CommerceRuntimeWriteInventory();
$findings = $inventory->scan(__DIR__ . '/..');
$counts = $inventory->count_by_classification($findings);
$candidates = $inventory->migration_candidates($findings);

cli_writeln('== I10F Commerce runtime inventory ==');

foreach ($counts as $classification => $count) {
    cli_writeln(sprintf('  %-34s %d', $classification, $count));
}

if (!empty($options['verbose'])) {
    foreach ($findings as $finding) {
        cli_writeln(sprintf(
            '  [%s] %s:%d %s table=%s — %s',
            strtoupper($finding->get_classification()),
            $finding->get_file(),
            $finding->get_line(),
            $finding->get_operation(),
            $finding->get_table() ?? 'dynamic/unknown',
            $finding->get_reason()
        ));
    }
} else {
    foreach ($candidates as $finding) {
        cli_writeln(sprintf(
            '  [CANDIDATE] %s:%d %s table=%s',
            $finding->get_file(),
            $finding->get_line(),
            $finding->get_operation(),
            $finding->get_table() ?? 'dynamic/unknown'
        ));
    }
}

if ($candidates !== []) {
    if (!empty($options['strict'])) {
        cli_error('Unclassified Commerce runtime writes remain.');
    }

    cli_writeln('[WARN] Review migration candidates before PRE-PROD certification.');
} else {
    cli_writeln('[OK] No unclassified Commerce runtime write remains.');
    cli_writeln(
        '[INFO] Legacy compatibility projections are retained intentionally for upgrade and rollback.'
    );
}
