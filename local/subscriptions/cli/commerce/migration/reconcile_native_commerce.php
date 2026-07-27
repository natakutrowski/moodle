<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params([
    'family' => null,
    'legacyid' => 0,
    'repair' => false,
    'help' => false,
], ['h' => 'help']);

if ($options['help'] || !in_array($options['family'], ['subscription', 'digital'], true) || (int)$options['legacyid'] <= 0) {
    cli_writeln('Usage: --family=subscription|digital --legacyid=ID [--repair]');
    exit($options['help'] ? 0 : 1);
}

$result = \local_subscriptions\commerce\reconciliation\CommerceReconciliationFactory::create()->reconcile(
    (string)$options['family'],
    (int)$options['legacyid'],
    !empty($options['repair'])
);

cli_writeln(json_encode([
    'family' => $result->get_family(),
    'legacyid' => $result->get_legacy_id(),
    'equal' => $result->is_equal(),
    'repaired' => $result->was_repaired(),
    'issues' => count($result->get_issues()),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
exit($result->is_equal() ? 0 : 2);
