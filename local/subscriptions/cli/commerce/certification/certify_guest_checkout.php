<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\guest\CommerceGuestCheckoutCertifier;

[$options, $unrecognized] = cli_get_params(
    ['session' => '', 'json' => false, 'help' => false],
    ['s' => 'session', 'j' => 'json', 'h' => 'help']
);
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "Commerce 7.95 H5.4 — Guest Checkout certification\n\n";
    echo "  --session=REFERENCE  Also certify one durable Guest Checkout session\n";
    echo "  --json               Output JSON\n";
    echo "  --help               Show help\n";
    exit(0);
}

$result = (new CommerceGuestCheckoutCertifier($DB, $CFG->dirroot . '/local/subscriptions'))
    ->certify(trim((string) $options['session']) ?: null);

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['certified'] ? 0 : 2);
}

echo "== Commerce 7.95 H5.4 — Guest Checkout certification ==\n\n";
foreach ($result['checks'] as $check) {
    echo "[{$check['status']}] {$check['key']} — {$check['message']}\n";
}
echo "\nVerdict: " . ($result['certified'] ? 'GUEST CHECKOUT CERTIFIED' : 'GUEST CHECKOUT NOT CERTIFIED') . "\n";
exit($result['certified'] ? 0 : 2);
