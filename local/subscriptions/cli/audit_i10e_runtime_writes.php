<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\rollout\CommerceRuntimeWriteInventory;

[$options] = cli_get_params(
    [
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

$inventory = new CommerceRuntimeWriteInventory();
$findings = $inventory->scan(__DIR__ . '/..');
$candidates = $inventory->migration_candidates($findings);
$counts = $inventory->count_by_classification($findings);

cli_writeln('== I10E compatibility entrypoint / I10F domain-aware inventory ==');

foreach ($counts as $classification => $count) {
    cli_writeln(sprintf('  %-34s %d', $classification, $count));
}

foreach ($candidates as $finding) {
    cli_writeln(sprintf(
        '  [CANDIDATE] %s:%d %s',
        $finding->get_file(),
        $finding->get_line(),
        $finding->get_operation()
    ));
}

if (!empty($options['strict']) && $candidates !== []) {
    cli_error('Unclassified Commerce runtime writes remain.');
}

cli_writeln(
    $candidates === []
        ? '[OK] No unclassified Commerce runtime writes detected.'
        : '[WARN] Commerce runtime migration candidates remain.'
);

cli_writeln(
    '[INFO] Use audit_i10f_commerce_runtime.php --verbose for the complete classification.'
);
