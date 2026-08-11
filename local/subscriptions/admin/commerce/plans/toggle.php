<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest');
}

$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', subscription_config::commerce_plans_page(), PARAM_LOCALURL);
$plan = $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST);
$plan->is_active = $plan->is_active ? 0 : 1;
$plan->last_update = time();
$DB->update_record('subscription_plan', $plan);

redirect(new moodle_url($returnurl), get_string('changessaved'));
