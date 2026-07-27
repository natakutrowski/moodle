<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'json' => false,
    'strict' => false,
    'details' => false,
], [
    'h' => 'help',
    'j' => 'json',
    's' => 'strict',
    'd' => 'details',
]);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    echo "Audit parity between mapped Legacy catalogue records and Native Commerce.\n";
    echo "Options: --json --strict --details\n";
    exit(0);
}

$report = (new CommerceCatalogFactory($DB))->parity_auditor()->audit();
if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    cli_heading('Phase 7.94B8 - Commerce catalogue parity');
    foreach (['checked', 'equal', 'different', 'missing'] as $key) {
        mtrace(sprintf('%-12s %d', $key . ':', $report[$key]));
    }
    if ($options['details']) {
        foreach ($report['details'] as $detail) {
            mtrace(json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }
}

$failed = $report['different'] > 0 || $report['missing'] > 0;
exit($options['strict'] && $failed ? 2 : 0);
