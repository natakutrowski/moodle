<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\recovery\CommerceCheckoutRecoveryService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'reference' => null,
    'purchase' => null,
    'payment' => null,
    'session' => null,
    'execute' => false,
    'json' => false,
], ['h' => 'help']);

if ($unrecognized || $options['help']) {
    $help = "Diagnose or repair one interrupted Native Commerce checkout.\n\n"
        . "Options:\n"
        . "--reference=REF   Native purchase reference\n"
        . "--purchase=UUID   Native purchase UUID\n"
        . "--payment=ID      Native payment attempt ID\n"
        . "--session=TOKEN   Guest Checkout token\n"
        . "--execute         Apply the safe idempotent repair plan\n"
        . "--json            JSON output\n";
    echo $help;
    exit($unrecognized ? 1 : 0);
}

$selectors = array_filter([
    'reference' => $options['reference'],
    'purchase' => $options['purchase'],
    'payment' => $options['payment'],
    'session' => $options['session'],
], static fn($value): bool => $value !== null && trim((string)$value) !== '');

if (count($selectors) !== 1) {
    cli_error('Exactly one selector is required: --reference, --purchase, --payment or --session.');
}

$kind = (string)array_key_first($selectors);
$identifier = (string)reset($selectors);
$service = CommerceCheckoutRecoveryService::create($DB);
$result = $options['execute']
    ? $service->execute($identifier, $kind)->to_array()
    : $service->diagnose($identifier, $kind)->to_array();

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$diagnostic = $options['execute'] ? $result['after'] : $result;
echo "Commerce checkout recovery\n";
echo str_repeat('=', 28) . "\n";
echo 'Purchase: ' . ($diagnostic['purchase']['reference'] ?? 'NOT FOUND') . "\n";
echo 'Status: ' . ($diagnostic['purchase']['status'] ?? '-') . "\n";
echo 'Payments: ' . count($diagnostic['payments']) . "\n";
echo 'Fulfillments: ' . count($diagnostic['fulfillments']) . "\n";
echo 'Issues: ' . ($diagnostic['issues'] === [] ? 'none' : implode(', ', $diagnostic['issues'])) . "\n";
echo 'Plan: ' . ($diagnostic['actions'] === [] ? 'nothing to do' : implode(', ', $diagnostic['actions'])) . "\n";
if ($options['execute']) {
    echo 'Executed: ' . ($result['executed_actions'] === [] ? 'none' : implode(', ', $result['executed_actions'])) . "\n";
    echo 'Replayed: ' . ($result['replayed'] ? 'yes' : 'no') . "\n";
}
