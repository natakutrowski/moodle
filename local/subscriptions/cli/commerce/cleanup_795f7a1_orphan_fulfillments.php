<?php

/**
 * 7.95F7A.1 orphan fulfillment inspection and cleanup.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceOrphanFulfillmentCleaner;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
        'execute' => false,
        'force' => false,
        'confirm' => '',
        'limit' => 0,
    ],
    [
        'h' => 'help',
        'j' => 'json',
        'x' => 'execute',
        'l' => 'limit',
    ]
);

if ($unrecognized) {
    cli_error("Unknown options:\n  " . implode("\n  ", $unrecognized));
}

if ($options['help']) {
    echo <<<HELP
7.95F7A.1 orphan fulfillment cleanup.

The command is read-only by default.

Options:
  -h, --help              Show this help.
  -j, --json              Print JSON output.
  -l, --limit=N           Inspect or process at most N rows. Zero means all.
  -x, --execute           Delete safe orphan fulfillment rows.
      --force             Also delete orphan fulfillment rows with detected dependencies.
                          Dependent records are never deleted.
      --confirm=VALUE     Required with --execute.
                          Expected value: DELETE-ORPHAN-FULFILLMENTS

Examples:
  php local/subscriptions/cli/commerce/cleanup_795f7a1_orphan_fulfillments.php
  php local/subscriptions/cli/commerce/cleanup_795f7a1_orphan_fulfillments.php --json
  php local/subscriptions/cli/commerce/cleanup_795f7a1_orphan_fulfillments.php \
      --execute --confirm=DELETE-ORPHAN-FULFILLMENTS

The command only deletes rows from local_subscriptions_commerce_fulfillment.
HELP;
    exit(0);
}

$limit = max(0, (int)$options['limit']);
$execute = !empty($options['execute']);
$force = !empty($options['force']);

if ($force && !$execute) {
    cli_error('--force is only valid with --execute.');
}

if ($execute && (string)$options['confirm'] !== 'DELETE-ORPHAN-FULFILLMENTS') {
    cli_error('Execution requires --confirm=DELETE-ORPHAN-FULFILLMENTS.');
}

$cleaner = new CommerceOrphanFulfillmentCleaner($DB);
$orphans = $cleaner->inspect($limit);
$safecount = count(array_filter($orphans, static fn(array $row): bool => $row['safe_to_delete']));
$blockedcount = count($orphans) - $safecount;

$result = null;
if ($execute) {
    $result = $cleaner->execute($force, $limit);
}

$output = [
    'phase' => '7.95F7A.1',
    'mode' => $execute ? 'execute' : 'dry-run',
    'force' => $force,
    'orphan_count' => count($orphans),
    'safe_count' => $safecount,
    'blocked_count' => $blockedcount,
    'orphans' => $orphans,
    'result' => $result,
];

if ($options['json']) {
    $encoded = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        cli_error('Unable to encode cleanup report as JSON.');
    }
    echo $encoded . PHP_EOL;
    exit(0);
}

cli_heading('Commerce 7.95F7A.1 — Orphan fulfillment cleanup');
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));
cli_writeln('Orphans: ' . count($orphans));
cli_writeln('Safe to delete: ' . $safecount);
cli_writeln('Blocked by dependencies: ' . $blockedcount);
cli_writeln('');

foreach ($orphans as $row) {
    cli_writeln(sprintf(
        '#%d purchase=%d status=%s key=%s safe=%s',
        $row['id'],
        $row['purchaseid'],
        $row['status'],
        $row['fulfillmentkey'],
        $row['safe_to_delete'] ? 'YES' : 'NO'
    ));
    cli_writeln('  reference: ' . $row['reference']);
    cli_writeln('  idempotency: ' . $row['idempotencykey']);
    if ($row['grantreference'] !== '') {
        cli_writeln('  grant reference: ' . $row['grantreference']);
    }
    cli_writeln('  dependencies: ' . json_encode(
        $row['dependencies'],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ));
}

if (!$execute) {
    cli_writeln('');
    cli_writeln('No database write performed.');
    cli_writeln('Run with --execute --confirm=DELETE-ORPHAN-FULFILLMENTS after reviewing the report.');
} else {
    cli_writeln('');
    cli_writeln('Deleted: ' . $result['deleted']);
    cli_writeln('Skipped: ' . $result['skipped']);
    cli_writeln('Deleted IDs: ' . ($result['deletedids'] === [] ? 'none' : implode(', ', $result['deletedids'])));
    cli_writeln('Skipped IDs: ' . ($result['skippedids'] === [] ? 'none' : implode(', ', $result['skippedids'])));
}

exit(0);
