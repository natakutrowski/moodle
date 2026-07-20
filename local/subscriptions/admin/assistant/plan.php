<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanRenderer;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
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

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$PAGE->set_title(
    get_string(
        'csplanpage',
        'local_subscriptions'
    )
);

$PAGE->set_heading(
    get_string(
        'csplanpage',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-assistant-page'
);
$PAGE->add_body_class(
    'local-subscriptions-customer-success-plan-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
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

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_crm_assistant_page()
    ),
    '← ' . get_string(
        'crm_assistant_title',
        'local_subscriptions'
    ),
    [
        'class' =>
            'crm-app-back-link btn btn-link ps-0 mb-3',
    ]
);

echo (new CustomerSuccessPlanRenderer())
    ->render_plan(
        $plan,
        $canmanage
    );

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();