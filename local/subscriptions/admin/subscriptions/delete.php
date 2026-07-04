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

$subscription = $DB->get_record('user_subscription', ['id' => $id], '*', MUST_EXIST);

$plan = $DB->get_record('subscription_plan', ['id' => $subscription->planid], '*', IGNORE_MISSING);

AdminLog::subscriptionDeleted($subscription, $plan ?: null);

subscription_manager::unenrol_user_from_plan((int)$subscription->userid, (int)$subscription->planid);
$DB->delete_records('user_subscription', ['id' => $id]);

redirect(
    new moodle_url(subscription_config::user_subscriptions_page()),
    get_string('subscription_deleted_successfully', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);