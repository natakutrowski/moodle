<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$id = required_param('id', PARAM_INT);
$plan = $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST);
$current = $DB->count_records_select('user_subscription', 'planid = :planid AND status IN (:active, :queued)', [
    'planid' => $id,
    'active' => 'active',
    'queued' => 'queued',
]);
if ($current > 0) {
    redirect(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $id]), get_string('commerce_plan_delete_blocked', 'local_subscriptions', $current), null, \core\output\notification::NOTIFY_ERROR);
}
$transaction = $DB->start_delegated_transaction();
$DB->delete_records('subscription_plan_translation', ['planid' => $id]);
$DB->delete_records('subscription_plan_price', ['planid' => $id]);
$DB->delete_records('subscription_plan_entitlement', ['planid' => $id]);
$DB->delete_records('subscription_plan_upgrade', ['fromplanid' => $id]);
$DB->delete_records('subscription_plan_upgrade', ['toplanid' => $id]);
$DB->delete_records('subscription_plan', ['id' => $id]);
$transaction->allow_commit();
redirect(new moodle_url(subscription_config::commerce_plans_page()), get_string('commerce_plan_deleted', 'local_subscriptions'));
