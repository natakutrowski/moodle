<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'apply' => false,
    ],
    [
        'h' => 'help',
        'a' => 'apply',
    ]
);

if (!empty($unrecognized)) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo "CampusFR 7.98D4 — Legacy Trial Date B expiry\n\n";
    echo "Dry-run (default):\n";
    echo "  php local/subscriptions/cli/commerce/maintenance/expire_798d_legacy_trials.php\n\n";
    echo "Apply immediately:\n";
    echo "  php local/subscriptions/cli/commerce/maintenance/expire_798d_legacy_trials.php --apply\n";
    exit(0);
}

$apply = !empty($options['apply']);
$service = \local_subscriptions\trial\TrialLegacyExpiryService::create();
$result = $service->expire_due(time(), !$apply);

$mode = $apply ? 'APPLY' : 'DRY-RUN';
echo "== CampusFR 7.98D4 — Legacy Trial Date B expiry ({$mode}) ==\n";
echo 'Date B due: ' . (!empty($result['due']) ? 'YES' : 'NO') . "\n";
echo 'Legacy expiry timestamp: ' . ($result['legacy_expire_at'] ?? 'not configured') . "\n";
echo 'Trial subscriptions to expire: ' . (int)$result['subscriptions_expired'] . "\n";
echo 'Campus Trial rows to expire: ' . (int)$result['campus_rows_expired'] . "\n";
echo 'trialstudent roles to remove: ' . (int)$result['trial_roles_removed'] . "\n";
echo 'Manual enrolments to suspend: ' . (int)$result['enrolments_suspended'] . "\n";
echo 'Courses preserved by stronger access: ' . (int)$result['courses_preserved'] . "\n";

if (empty($result['due'])) {
    echo "No change: Date B is not configured or has not been reached.\n";
} elseif (!$apply) {
    echo "No database change performed. Re-run with --apply to execute immediately.\n";
} else {
    echo "Date B expiry applied. Moodle accounts and progress were not deleted.\n";
}
