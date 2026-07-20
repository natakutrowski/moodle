<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandCenterRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\onboarding\HelpOnboardingRenderer;
use local_subscriptions\crm\help\onboarding\HelpOnboardingService;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\dashboard\Dashboard;
use local_subscriptions\dashboard\services\DashboardPeriod;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$period = DashboardPeriod::normalize(
    optional_param(
        'period',
        DashboardPeriod::TODAY,
        PARAM_ALPHA
    )
);

$pageurl = new moodle_url(
    subscription_config::admin_dashboard_page(),
    [
        'period' => $period,
    ]
);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title(
    get_string(
        'admin_dashboard',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'admin_dashboard',
        'local_subscriptions'
    )
);
$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-dashboard-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::
            plugin_stylesheet_page()
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/command_center',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/dashboard_currency',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::DASHBOARD,
    $context
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

$onboardingstate =
    (new HelpOnboardingService())
        ->get_state((int)$USER->id);

if (!$onboardingstate->finished) {
    echo HelpOnboardingRenderer::render(
        $onboardingstate,
        $pageurl->out_as_local_url(false),
        true
    );
}

echo html_writer::start_div(
    'local-subscriptions-workspace-command'
);

echo CommandCenterRenderer::render();

echo html_writer::end_div();

echo Dashboard::render($period);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();