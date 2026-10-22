<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\trial\TrialLegacyTransitionAuditService;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'json' => false,
    'strict' => false,
], [
    'h' => 'help',
]);

if ($unrecognized !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo "CampusFR 7.98D Legacy Trial transition audit.\n\n";
    echo "Options:\n";
    echo "  --json      Emit the complete report as JSON.\n";
    echo "  --strict    Exit non-zero on invalid transition configuration or duration divergence.\n";
    echo "  -h, --help  Show this help.\n";
    exit(0);
}

$report = TrialLegacyTransitionAuditService::create()->inspect();

if (!empty($options['json'])) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    $transition = $report['transition'];
    $duration = $report['duration_configuration'];
    $campus = $report['campus_trial'];
    $subscriptions = $report['subscriptions'];
    $roles = $report['roles'];
    $conversion = $report['native_trialconversion'];
    $consistency = $report['consistency'];

    cli_heading('CampusFR 7.98D5 — Legacy Trial transition audit');
    cli_writeln('Generated: ' . userdate((int)$report['generated_at']));
    cli_writeln('Site timezone: ' . (string)$transition['timezone']);
    cli_writeln('Current phase: ' . (string)$transition['phase']);
    cli_writeln('New Trial registrations open: ' . (!empty($transition['registrations_open']) ? 'YES' : 'NO'));
    cli_writeln('Legacy conversion window open: ' . (!empty($transition['conversion_window_open']) ? 'YES' : 'NO'));
    cli_writeln('Registration close: ' . format_transition_date($transition['registration_close_at']));
    cli_writeln('Legacy expiry: ' . format_transition_date($transition['legacy_expire_at']));

    cli_writeln('');
    cli_heading('Configuration');
    cli_writeln('Trial plan ID: ' . (int)$report['trial_plan_id']);
    cli_writeln('Duration authority: ' . (string)$duration['authority_component'] . '/' . (string)$duration['authority_key']);
    cli_writeln('Authoritative duration days: ' . (int)$duration['authoritative_days']);
    cli_writeln('Legacy local_campus/trialdays (ignored): ' . (int)$duration['legacy_local_campus_trialdays']);
    cli_writeln('Legacy value differs: ' . (!empty($duration['legacy_differs']) ? 'YES' : 'NO'));

    foreach ($report['configuration_issues'] as $issue) {
        cli_writeln(sprintf(
            '[%s] %s — %s',
            strtoupper((string)$issue['severity']),
            (string)$issue['code'],
            (string)$issue['message']
        ));
    }

    cli_writeln('');
    cli_heading('Legacy Campus Trial rows');
    cli_writeln('Table available: ' . (!empty($campus['available']) ? 'YES' : 'NO'));
    cli_writeln('Total: ' . (int)$campus['total']);
    cli_writeln('Linked to Moodle user: ' . (int)$campus['linked_to_user']);
    cli_writeln('Status active: ' . (int)$campus['status_active']);
    cli_writeln('Status expired: ' . (int)$campus['status_expired']);
    cli_writeln('Status banned: ' . (int)$campus['status_banned']);
    cli_writeln('Effectively active now: ' . (int)$campus['effective_active']);
    cli_writeln('Past expiresat: ' . (int)$campus['past_expiry']);

    cli_writeln('');
    cli_heading('Native Trial subscriptions');
    cli_writeln('Table available: ' . (!empty($subscriptions['available']) ? 'YES' : 'NO'));
    cli_writeln('Total rows for Trial plan: ' . (int)$subscriptions['total']);
    cli_writeln('Distinct users: ' . (int)$subscriptions['distinct_users']);
    cli_writeln('Active and within end_date: ' . (int)$subscriptions['active_valid']);
    cli_writeln('Active but past end_date: ' . (int)$subscriptions['active_past_end']);
    foreach ($subscriptions['by_status'] as $status => $count) {
        cli_writeln('  status[' . $status . ']: ' . (int)$count);
    }

    cli_writeln('');
    cli_heading('Trial roles');
    foreach (['trialstudent', 'triallimited'] as $shortname) {
        $role = $roles[$shortname];
        cli_writeln(sprintf(
            '%s: exists=%s assignments=%d users=%d',
            $shortname,
            !empty($role['role_exists']) ? 'YES' : 'NO',
            (int)$role['assignments'],
            (int)$role['distinct_users']
        ));
    }

    cli_writeln('');
    cli_heading('Native Trial conversions observed');
    cli_writeln('Commerce tables available: ' . (!empty($conversion['available']) ? 'YES' : 'NO'));
    cli_writeln('Trialconversion items: ' . (int)$conversion['items']);
    cli_writeln('Purchases containing Trialconversion: ' . (int)$conversion['purchases']);
    cli_writeln('Distinct purchasing users: ' . (int)$conversion['users']);

    cli_writeln('');
    cli_heading('Cross-plugin consistency');
    cli_writeln('Comparable: ' . (!empty($consistency['available']) ? 'YES' : 'NO'));
    cli_writeln(
        'Campus users without Trial subscription: ' .
        (int)$consistency['campus_users_without_trial_subscription']
    );
    cli_writeln(
        '  active inconsistency: ' .
        (int)$consistency['campus_active_users_without_trial_subscription']
    );
    cli_writeln(
        '  historical-only rows: ' .
        (int)$consistency['campus_historical_users_without_trial_subscription']
    );
    cli_writeln(
        'Trial subscription users without Campus row: ' .
        (int)$consistency['trial_subscription_users_without_campus_row']
    );
}

$hasconfigurationerror = false;
foreach ($report['configuration_issues'] as $issue) {
    if (($issue['severity'] ?? '') === 'error') {
        $hasconfigurationerror = true;
        break;
    }
}

$strictfailure = !empty($options['strict']) && (
    $hasconfigurationerror ||
    !empty($report['consistency']['campus_active_users_without_trial_subscription']) ||
    !empty($report['roles']['triallimited']['assignments'])
);

exit($strictfailure ? 1 : 0);

/**
 * @param mixed $timestamp
 */
function format_transition_date($timestamp): string {
    $timestamp = (int)$timestamp;
    return $timestamp > 0 ? userdate($timestamp) : 'not configured';
}
