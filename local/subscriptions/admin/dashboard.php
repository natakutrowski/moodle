<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\dashboard\Dashboard;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\crm\layout\CrmPageConfigurator;

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

$pagetitle = get_string(
    'admin_dashboard',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-dashboard-page'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_edit_mode',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_drag_drop',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_item_menu',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/dashboard_workspace_actions',
    'init',
    [
        [
            'routes' => [
                'stats' => (
                    new moodle_url(
                        subscription_config::
                            user_subscriptions_page()
                    )
                )->out(false),

                'intelligence' => (
                    new moodle_url(
                        subscription_config::
                            admin_users_page()
                    )
                )->out(false),

                'assistant' => (
                    new moodle_url(
                        subscription_config::
                            admin_crm_assistant_page()
                    )
                )->out(false),

                'inbox' => (
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page()
                    )
                )->out(false),

                'work' => (
                    new moodle_url(
                        subscription_config::
                            admin_work_items_page()
                    )
                )->out(false),

                'priorities' => (
                    new moodle_url(
                        subscription_config::
                            admin_crm_assistant_page()
                    )
                )->out(false),

                'funnel' => (
                    new moodle_url(
                        subscription_config::
                            admin_users_page()
                    )
                )->out(false),

                'trends' => (
                    new moodle_url(
                        subscription_config::
                            admin_users_page()
                    )
                )->out(false),

                'intelligence_alerts' => (
                    new moodle_url(
                        subscription_config::
                            admin_users_page()
                    )
                )->out(false),

                'team' => (
                    new moodle_url(
                        subscription_config::
                            admin_users_page()
                    )
                )->out(false),
            ],
        ],
    ]
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_personalization',
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

echo Dashboard::render(
    $period,
    $pageurl->out_as_local_url(false)
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();