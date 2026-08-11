<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$id = required_param('id', PARAM_INT);
$scope = $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST);
$plancount = $DB->count_records('subscription_plan', ['accessscopeid' => $id]);
if ($plancount > 0) {
    redirect(new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $id]), get_string('commerce_scope_delete_blocked', 'local_subscriptions', $plancount), null, \core\output\notification::NOTIFY_ERROR);
}
$transaction = $DB->start_delegated_transaction();
$DB->delete_records('subscription_access_scope_translation', ['accessscopeid' => $id]);
$DB->delete_records('subscription_access_scope', ['id' => $id]);
$transaction->allow_commit();
redirect(new moodle_url(subscription_config::commerce_access_scopes_page()), get_string('commerce_scope_deleted', 'local_subscriptions'));
