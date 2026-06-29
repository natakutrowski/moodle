<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();
subscription_config::guard_public_access();

global $DB;

$id = required_param('id', PARAM_INT);

$subscription = $DB->get_record('user_subscription', ['id' => $id], '*', MUST_EXIST);

subscription_manager::unenrol_user_from_plan((int)$subscription->userid, (int)$subscription->planid);
$DB->delete_records('user_subscription', ['id' => $id]);

redirect(
    new moodle_url(subscription_config::user_subscriptions_page()),
    get_string('subscription_deleted_successfully', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);