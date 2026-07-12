<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\Dashboard;
use local_subscriptions\commandcenter\CommandCenterRenderer;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\onboarding\HelpOnboardingService;
use local_subscriptions\crm\help\onboarding\HelpOnboardingRenderer;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(Capabilities::VIEW_DASHBOARD);

$period = DashboardPeriod::normalize(optional_param('period', DashboardPeriod::TODAY, PARAM_ALPHA));

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::admin_dashboard_page(), ['period' => $period]));
$PAGE->set_title(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->set_heading(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->add_body_class('local-subscriptions-crm-workspace');
$PAGE->add_body_class('local-subscriptions-dashboard-page');
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));
$PAGE->requires->js_call_amd('local_subscriptions/command_center', 'init');

echo $OUTPUT->header();

echo html_writer::start_div(
    'local-subscriptions-crm-workspace-shell'
);

echo CrmPageHeader::render(
    get_string(
        'admin_dashboard',
        'local_subscriptions'
    ),
    get_string(
        'admin_dashboard_description',
        'local_subscriptions'
    ),
    HelpContext::DASHBOARD
);

$dashboardurl = new moodle_url(
    subscription_config::admin_dashboard_page(),
    [
        'period' => $period,
    ]
);

$onboardingstate = (new HelpOnboardingService())
    ->get_state((int)$USER->id);

if (!$onboardingstate->finished) {
    echo HelpOnboardingRenderer::render(
        $onboardingstate,
        $dashboardurl->out_as_local_url(false),
        true
    );
}

echo html_writer::start_div(
    'local-subscriptions-workspace-command'
);

echo CommandCenterRenderer::render();

echo html_writer::end_div();

echo Dashboard::render($period);

echo html_writer::end_div();

echo $OUTPUT->footer();