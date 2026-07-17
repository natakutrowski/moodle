<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanRenderer;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
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

$plan =
    (new CustomerSuccessPlanReadRepository())
        ->get($planid);

$canmanage =
    Capabilities::can_manage_users(
        $context
    );

echo $OUTPUT->header();

echo (new CustomerSuccessPlanRenderer())
    ->render_plan(
        $plan,
        $canmanage
    );

echo $OUTPUT->footer();