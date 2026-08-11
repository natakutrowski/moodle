<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountPurgeService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutLifecycleService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

[$options, $unrecognized] = cli_get_params(
    ['execute' => false, 'json' => false, 'help' => false],
    ['e' => 'execute', 'j' => 'json', 'h' => 'help']
);
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "Commerce 7.95 H5.3 — Expired Guest Checkout cleanup\n\n";
    echo "  --execute  Apply safe expiry and purge operations\n";
    echo "  --json     Output JSON\n";
    echo "  --help     Show help\n";
    exit(0);
}

$repository = new CommerceGuestCheckoutSessionRepository($DB);
$service = new CommerceGuestAccountPurgeService(
    $DB,
    $repository,
    new CommerceGuestCheckoutLifecycleService($repository)
);
$execute = (bool) $options['execute'];
$results = [];
foreach ($repository->find_expired(time()) as $session) {
    $results[] = $service->process($session, $execute);
}

$summary = [
    'phase' => '7.95H5.3',
    'mode' => $execute ? 'execute' : 'dry-run',
    'processed' => count($results),
    'purgeable' => count(array_filter($results, static fn(array $row): bool => $row['purgeable'])),
    'retained' => count(array_filter($results, static fn(array $row): bool => !$row['purgeable'])),
    'results' => $results,
];

if ($options['json']) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "== Commerce 7.95 H5.3 — Expired Guest Checkout cleanup ==\n";
echo 'Mode: ' . strtoupper($summary['mode']) . "\n";
echo 'Processed: ' . $summary['processed'] . "\n";
echo 'Purgeable: ' . $summary['purgeable'] . "\n";
echo 'Retained: ' . $summary['retained'] . "\n\n";
foreach ($results as $result) {
    echo sprintf(
        "%s | %s | user=%s | reasons=%s\n",
        $result['reference'],
        $result['action'],
        $result['userid'] ?? '-',
        $result['reasons'] === [] ? '-' : implode(',', $result['reasons'])
    );
}
if (!$execute) {
    echo "\nDry-run only. Re-run with --execute to apply safe operations.\n";
}
