<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminLog;

AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

global $DB;

$id = required_param('id', PARAM_INT);

$returnto = optional_param(
    'returnto',
    'list',
    PARAM_ALPHA
);

$subscription = $DB->get_record('user_subscription', ['id' => $id], '*', MUST_EXIST);

$userid = (int)$subscription->userid;

$plan = $DB->get_record('subscription_plan', ['id' => $subscription->planid], '*', IGNORE_MISSING);

AdminLog::subscriptionDeleted($subscription, $plan ?: null);

subscription_manager::unenrol_user_from_plan((int)$subscription->userid, (int)$subscription->planid);
$DB->delete_records('user_subscription', ['id' => $id]);

if ($returnto === 'user') {
    $returnurl = new moodle_url(
        subscription_config::
            admin_user_view_page(),
        [
            'id' => $userid,
        ]
    );
} else {
    $returnurl = new moodle_url(
        subscription_config::
            user_subscriptions_page()
    );
}

redirect(
    $returnurl,
    get_string(
        'subscription_deleted_successfully',
        'local_subscriptions'
    ),
    null,
    \core\output\notification::
        NOTIFY_SUCCESS
);