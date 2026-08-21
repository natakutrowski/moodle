<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanRenderer;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$planid = required_param(
    'id',
    PARAM_INT
);

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

$url = new moodle_url(
    subscription_config::
        admin_customer_success_plan_page(),
    [
        'id' => $planid,
    ]
);

$pagetitle = get_string(
    'csplanpage',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    [
        'local-subscriptions-assistant-page',
        'local-subscriptions-customer-success-plan-page',
    ]
);

$plan =
    (new CustomerSuccessPlanReadRepository())
        ->get($planid);

$canmanage =
    Capabilities::can_manage_users(
        $context
    );

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::ASSISTANT,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_assistant_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_crm_assistant_page()
                ),
        ],
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);


echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_customer_success_plan_subtitle',
        'local_subscriptions'
    ),
    HelpContext::ASSISTANT
);

echo (new CustomerSuccessPlanRenderer())
    ->render_plan(
        $plan,
        $canmanage
    );

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();