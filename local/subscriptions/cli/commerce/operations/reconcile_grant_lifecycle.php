<?php

/**
 * Reconciles planned Native Grants whose fulfillment is already completed.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\entitlement\lifecycle\CommerceGrantLifecycleReconciler;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
        'execute' => false,
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
Commerce 7.95 H4.6 — Native Grant lifecycle reconciliation.

The command is read-only by default. It finds Grants with:
  grant.status = planned
  fulfillment_state.status = completed

Options:
  -h, --help              Show this help.
  -j, --json              Print JSON output.
  -l, --limit=N           Inspect or process at most N Grants. Zero means all.
  -x, --execute           Change matching Grants from planned to active.
      --confirm=VALUE     Required with --execute.
                          Expected value: ACTIVATE-COMPLETED-GRANTS

Examples:
  php local/subscriptions/cli/commerce/operations/reconcile_grant_lifecycle.php
  php local/subscriptions/cli/commerce/operations/reconcile_grant_lifecycle.php --json
  php local/subscriptions/cli/commerce/operations/reconcile_grant_lifecycle.php \
      --execute --confirm=ACTIVATE-COMPLETED-GRANTS
HELP;
    exit(0);
}

$limit = max(0, (int) $options['limit']);
$execute = !empty($options['execute']);

if ($execute && (string) $options['confirm'] !== 'ACTIVATE-COMPLETED-GRANTS') {
    cli_error('Execution requires --confirm=ACTIVATE-COMPLETED-GRANTS.');
}

$reconciler = new CommerceGrantLifecycleReconciler($DB);
$candidates = $reconciler->inspect($limit);
$result = $execute ? $reconciler->execute($limit) : null;

$output = [
    'phase' => '7.95H4.6',
    'mode' => $execute ? 'execute' : 'dry-run',
    'candidate_count' => count($candidates),
    'candidates' => $candidates,
    'result' => $result,
];

if ($options['json']) {
    $encoded = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        cli_error('Unable to encode Grant lifecycle report as JSON.');
    }

    echo $encoded . PHP_EOL;
    exit(0);
}

cli_heading('Commerce 7.95 H4.6 — Native Grant lifecycle');
cli_writeln('Mode: ' . ($execute ? 'EXECUTE' : 'DRY-RUN'));
cli_writeln('Candidates: ' . count($candidates));
cli_writeln('');

foreach ($candidates as $candidate) {
    cli_writeln(sprintf(
        '#%d grant=%s purchase=%s type=%s',
        $candidate['id'],
        $candidate['grantreference'],
        $candidate['purchasereference'],
        $candidate['type']
    ));
    cli_writeln('  resource: ' . $candidate['resourcekey']);
    cli_writeln('  lifecycle: planned -> active');
    cli_writeln('  fulfillment: completed');
    cli_writeln('  handler: ' . $candidate['handlerclass']);
}

cli_writeln('');
if (!$execute) {
    cli_writeln('No database write performed.');
    cli_writeln('Run with --execute --confirm=ACTIVATE-COMPLETED-GRANTS after reviewing the report.');
} else {
    cli_writeln('Activated: ' . $result['activated']);
    cli_writeln('Skipped: ' . $result['skipped']);
    cli_writeln('Activated IDs: ' . ($result['activatedids'] === [] ? 'none' : implode(', ', $result['activatedids'])));
}

exit(0);
