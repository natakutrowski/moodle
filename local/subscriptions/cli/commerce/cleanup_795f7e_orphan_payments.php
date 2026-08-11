<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceOrphanPaymentCleaner;

[$options, $unrecognized] = cli_get_params(
    [
        'execute' => false,
        'confirm' => null,
        'json' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo "Commerce 7.95F7E — orphan payment cleanup\n\n";
    echo "Dry-run:\n";
    echo "  php local/subscriptions/cli/commerce/cleanup_795f7e_orphan_payments.php\n\n";
    echo "Execute:\n";
    echo "  php local/subscriptions/cli/commerce/cleanup_795f7e_orphan_payments.php \\\n    --execute --confirm=DELETE-ORPHAN-PAYMENTS\n\n";
    echo "Options:\n";
    echo "  --json       Return JSON\n";
    echo "  --execute    Delete verified orphan payment rows\n";
    exit(0);
}

$execute = !empty($options['execute']);
if ($execute && $options['confirm'] !== 'DELETE-ORPHAN-PAYMENTS') {
    cli_error('Execution requires --confirm=DELETE-ORPHAN-PAYMENTS');
}

$cleaner = new CommerceOrphanPaymentCleaner($DB);
$orphans = $cleaner->inspect();
$result = $execute ? $cleaner->execute($orphans) : null;

$payload = [
    'phase' => '7.95F7E',
    'mode' => $execute ? 'execute' : 'dry-run',
    'orphan_count' => count($orphans),
    'safe_count' => count(array_filter($orphans, static fn(array $row): bool => !empty($row['safe_to_delete']))),
    'orphans' => $orphans,
    'result' => $result,
];

if (!empty($options['json'])) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "== Commerce 7.95F7E — Orphan payment cleanup ==\n";
echo 'Mode: ' . strtoupper($payload['mode']) . PHP_EOL;
echo 'Orphans: ' . $payload['orphan_count'] . PHP_EOL;
echo 'Safe to delete: ' . $payload['safe_count'] . PHP_EOL . PHP_EOL;

foreach ($orphans as $orphan) {
    echo sprintf(
        "#%d purchase=%d status=%s provider=%s amount=%d %s safe=YES\n",
        $orphan['id'],
        $orphan['purchaseid'],
        $orphan['status'],
        $orphan['provider'] !== '' ? $orphan['provider'] : '-',
        $orphan['amountminor'],
        $orphan['currency']
    );
    echo '  provider reference: ' . ($orphan['providerreference'] !== '' ? $orphan['providerreference'] : '-') . PHP_EOL;
    echo '  transaction: ' . ($orphan['transactionid'] !== '' ? $orphan['transactionid'] : '-') . PHP_EOL;
}

if (!$execute) {
    echo "\nNo database write performed.\n";
    echo "Run with --execute --confirm=DELETE-ORPHAN-PAYMENTS after reviewing the report.\n";
    exit(0);
}

echo "\nDeleted: {$result['deleted']}\n";
echo "Skipped: {$result['skipped']}\n";
echo 'Deleted IDs: ' . ($result['deletedids'] ? implode(', ', $result['deletedids']) : 'none') . PHP_EOL;
echo 'Skipped IDs: ' . ($result['skippedids'] ? implode(', ', $result['skippedids']) : 'none') . PHP_EOL;
