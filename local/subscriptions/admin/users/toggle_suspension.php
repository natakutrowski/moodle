<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminEvents;

global $DB;

AdminSecurity::require(Capabilities::MANAGE_USERS);
require_sesskey();

$id = required_param('id', PARAM_INT);

$user = $DB->get_record('user', [
    'id' => $id,
    'deleted' => 0,
], '*', MUST_EXIST);

$newsuspended = !empty($user->suspended) ? 0 : 1;

$user->suspended = $newsuspended;
$user->timemodified = time();

user_update_user($user, false, false);

AdminLog::log(
    $newsuspended
        ? AdminEvents::USER_SUSPENDED
        : AdminEvents::USER_REACTIVATED,
    $id,
    'user',
    $id
);

redirect(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]),
    $newsuspended
        ? get_string('crm_moodle_profile_suspended', 'local_subscriptions')
        : get_string('crm_moodle_profile_activated', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);