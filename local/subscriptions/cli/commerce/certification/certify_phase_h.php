<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\phaseh\CommercePhaseHCertifier;

[$options, $unrecognized] = cli_get_params([
    'guest' => '',
    'reference' => '',
    'purchase' => '',
    'payment' => '',
    'session' => '',
    'course' => '',
    'digital' => '',
    'bundle' => '',
    'json' => false,
    'help' => false,
], [
    'g' => 'guest',
    'r' => 'reference',
    'p' => 'purchase',
    'c' => 'course',
    'd' => 'digital',
    'b' => 'bundle',
    'j' => 'json',
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo "Commerce 7.95 H7 — Final phase H certification\n\n";
    echo "Architecture-only certification:\n";
    echo "  php certify_phase_h.php\n\n";
    echo "Optional real-data certification:\n";
    echo "  --guest=REFERENCE   Guest Checkout durable session reference\n";
    echo "  --reference=REFERENCE        Recovery diagnosis by Purchase reference\n";
    echo "  --purchase=UUID              Recovery diagnosis by Purchase UUID\n";
    echo "  --payment=ID                 Recovery diagnosis by Native Payment ID\n";
    echo "  --session=TOKEN              Recovery diagnosis by Guest Checkout token\n";
    echo "  --course=REFERENCE           Certify a fulfilled course Purchase\n";
    echo "  --digital=REFERENCE          Certify a fulfilled digital Purchase\n";
    echo "  --bundle=REFERENCE           Certify a fulfilled bundle Purchase\n";
    echo "  --json                       Output JSON\n";
    echo "  --help                       Show this help\n";
    exit(0);
}

$transaction = '';
$transactionkind = 'reference';
foreach (['reference', 'purchase', 'payment', 'session'] as $kind) {
    if (trim((string)$options[$kind]) !== '') {
        if ($transaction !== '') {
            cli_error('Use only one recovery selector: --reference, --purchase, --payment or --session.');
        }
        $transaction = trim((string)$options[$kind]);
        $transactionkind = $kind;
    }
}

$report = (new CommercePhaseHCertifier($DB, $CFG->dirroot . '/local/subscriptions'))->certify([
    'guest' => trim((string)$options['guest']),
    'transaction' => $transaction,
    'transactionkind' => $transactionkind,
    'course' => trim((string)$options['course']),
    'digital' => trim((string)$options['digital']),
    'bundle' => trim((string)$options['bundle']),
]);
$result = $report->to_array();

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['certified'] ? 0 : 2);
}

echo "====================================\n";
echo "Commerce 7.95H Certification\n";
echo "====================================\n\n";
foreach ($result['sections'] as $section) {
    echo $section['label'] . "\n";
    foreach ($section['checks'] as $check) {
        echo '  [' . $check['status'] . '] ' . $check['key'] . ' — ' . $check['message'] . "\n";
    }
    echo "\n";
}

echo 'Summary: '
    . $result['summary']['pass'] . ' PASS, '
    . $result['summary']['warn'] . ' WARN, '
    . $result['summary']['fail'] . ' FAIL, '
    . $result['summary']['skip'] . " SKIP\n\n";
echo $result['certified']
    ? "COMMERCE 7.95H CERTIFIED\n"
    : "COMMERCE 7.95H NOT CERTIFIED\n";
exit($result['certified'] ? 0 : 2);
