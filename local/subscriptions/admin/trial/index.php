<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;
use local_subscriptions\trial\TrialLegacyTransitionAuditService;
use local_subscriptions\trial\TrialLegacyTransitionPolicy;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$url = new moodle_url('/local/subscriptions/admin/trial/index.php');
$title = get_string('trial_admin_status_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-trial-transition-status'
);

$report = TrialLegacyTransitionAuditService::create()->inspect();
$transition = $report['transition'];
$consistency = $report['consistency'];
$roles = $report['roles'];
$subscriptions = $report['subscriptions'];
$campus = $report['campus_trial'];

$configerrors = array_values(array_filter(
    $report['configuration_issues'],
    static fn(array $issue): bool => ($issue['severity'] ?? '') === 'error'
));
$activeinconsistency = (int)($consistency['campus_active_users_without_trial_subscription'] ?? 0);
$triallimited = (int)($roles['triallimited']['assignments'] ?? 0);
$trialstudent = (int)($roles['trialstudent']['assignments'] ?? 0);
$activepastend = (int)($subscriptions['active_past_end'] ?? 0);
$activevalid = (int)($subscriptions['active_valid'] ?? 0);
$campuseffectiveactive = (int)($campus['effective_active'] ?? 0);
$legacyclosed = (string)$transition['phase'] === TrialLegacyTransitionPolicy::PHASE_LEGACY_CLOSED;
$healthy = $configerrors === []
    && $activeinconsistency === 0
    && $triallimited === 0
    && $activepastend === 0
    && (!$legacyclosed || ($trialstudent === 0 && $activevalid === 0 && $campuseffectiveactive === 0));

$phasekey = match ((string)$transition['phase']) {
    TrialLegacyTransitionPolicy::PHASE_REGISTRATION_OPEN => 'trial_admin_phase_registration_open',
    TrialLegacyTransitionPolicy::PHASE_CONVERSION_ONLY => 'trial_admin_phase_conversion_only',
    default => 'trial_admin_phase_legacy_closed',
};

$formatdate = static function (?int $timestamp): string {
    return !empty($timestamp)
        ? userdate((int)$timestamp, get_string('strftimedatetime'))
        : get_string('trial_admin_not_configured', 'local_subscriptions');
};

$metric = static function (string $label, string $value): string {
    return html_writer::div(
        html_writer::div(s($label), 'text-muted small')
        . html_writer::div(s($value), 'h4 mb-0'),
        'card card-body h-100'
    );
};

$healthbadge = html_writer::span(
    get_string(
        $healthy ? 'trial_admin_health_ok' : 'trial_admin_health_attention',
        'local_subscriptions'
    ),
    $healthy ? 'badge bg-success' : 'badge bg-warning text-dark'
);

$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_subscriptions_settings']);

$summary = html_writer::div(
    html_writer::div(
        html_writer::tag('h3', get_string($phasekey, 'local_subscriptions'), ['class' => 'mb-2'])
        . html_writer::div($healthbadge, 'mb-3')
        . html_writer::tag('p', get_string('trial_admin_status_intro', 'local_subscriptions'), ['class' => 'mb-3'])
        . html_writer::link(
            $settingsurl,
            get_string('trial_admin_open_settings', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-sm']
        ),
        'card card-body mb-4'
    )
);

$dates = html_writer::div(
    html_writer::div(
        $metric(
            get_string('trial_admin_date_a', 'local_subscriptions'),
            $formatdate($transition['registration_close_at'] ?? null)
        ),
        'col-md-6 mb-3'
    )
    . html_writer::div(
        $metric(
            get_string('trial_admin_date_b', 'local_subscriptions'),
            $formatdate($transition['legacy_expire_at'] ?? null)
        ),
        'col-md-6 mb-3'
    ),
    'row'
);

$health = html_writer::div(
    html_writer::div($metric(
        get_string('trial_admin_metric_native_active', 'local_subscriptions'),
        (string)((int)($subscriptions['active_valid'] ?? 0))
    ), 'col-md-3 mb-3')
    . html_writer::div($metric(
        get_string('trial_admin_metric_trialstudent', 'local_subscriptions'),
        (string)((int)($roles['trialstudent']['assignments'] ?? 0))
    ), 'col-md-3 mb-3')
    . html_writer::div($metric(
        get_string('trial_admin_metric_historical_only', 'local_subscriptions'),
        (string)((int)($consistency['campus_historical_users_without_trial_subscription'] ?? 0))
    ), 'col-md-3 mb-3')
    . html_writer::div($metric(
        get_string('trial_admin_metric_converted_users', 'local_subscriptions'),
        (string)((int)($report['native_trialconversion']['users'] ?? 0))
    ), 'col-md-3 mb-3'),
    'row'
);

$diagnostics = [];
foreach ($report['configuration_issues'] as $issue) {
    $diagnostics[] = html_writer::tag(
        'li',
        s((string)($issue['message'] ?? '')),
        ['class' => ($issue['severity'] ?? '') === 'error' ? 'text-danger' : 'text-warning']
    );
}
if ($activeinconsistency > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_active_gap', 'local_subscriptions', $activeinconsistency)), ['class' => 'text-danger']);
}
if ($triallimited > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_triallimited', 'local_subscriptions', $triallimited)), ['class' => 'text-danger']);
}
if ($activepastend > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_active_past_end', 'local_subscriptions', $activepastend)), ['class' => 'text-danger']);
}
if ($legacyclosed && $trialstudent > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_trialstudent_residual', 'local_subscriptions', $trialstudent)), ['class' => 'text-danger']);
}
if ($legacyclosed && $activevalid > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_native_active_after_b', 'local_subscriptions', $activevalid)), ['class' => 'text-danger']);
}
if ($legacyclosed && $campuseffectiveactive > 0) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_campus_active_after_b', 'local_subscriptions', $campuseffectiveactive)), ['class' => 'text-danger']);
}
if ($diagnostics === []) {
    $diagnostics[] = html_writer::tag('li', s(get_string('trial_admin_diag_none', 'local_subscriptions')), ['class' => 'text-success']);
}

$diagnosticcard = html_writer::div(
    html_writer::tag('h4', get_string('trial_admin_diagnostics_title', 'local_subscriptions'), ['class' => 'mb-3'])
    . html_writer::tag('ul', implode('', $diagnostics), ['class' => 'mb-0']),
    'card card-body mb-4'
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_commerce_page()),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('trial_admin_status_description', 'local_subscriptions'),
    HelpContext::SUBSCRIPTIONS
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::TRIAL,
    $context
);
echo $summary;
echo $dates;
echo $health;
echo $diagnosticcard;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
