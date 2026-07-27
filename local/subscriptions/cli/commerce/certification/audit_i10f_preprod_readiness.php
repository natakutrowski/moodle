<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\rollout\CommerceI10fReadinessService;

[$options] = cli_get_params(
    [
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

$report = (new CommerceI10fReadinessService())->build(__DIR__ . '/..');

cli_writeln('== I10F PRE-PROD technical readiness ==');

foreach ($report->get_counts() as $classification => $count) {
    cli_writeln(sprintf('  %-34s %d', $classification, $count));
}

cli_writeln(sprintf(
    '  %-34s %s',
    'migration_safety',
    $report->get_safety()->is_safe_for_preprod() ? 'ok' : 'review'
));

if ($report->get_flag_issues() === []) {
    cli_writeln(sprintf('  %-34s %s', 'rollout_flags', 'ok'));
} else {
    foreach ($report->get_flag_issues() as $issue) {
        cli_writeln('  [WARN] ' . $issue);
    }
}

cli_writeln('');
cli_writeln(
    '[INFO] Technical readiness does not replace Stripe, Alfa, email, cron and idempotency smoke tests.'
);

if (!$report->is_ready_for_functional_certification() && !empty($options['strict'])) {
    cli_error('I10F technical readiness checks are not complete.');
}

cli_writeln(
    $report->is_ready_for_functional_certification()
        ? '[OK] Ready for functional PRE-PROD certification.'
        : '[WARN] Resolve technical findings before functional certification.'
);
