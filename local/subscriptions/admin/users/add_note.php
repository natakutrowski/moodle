<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserNoteService;

global $DB;

AdminSecurity::require(Capabilities::VIEW_USERS);
require_sesskey();

$userid = required_param('id', PARAM_INT);
$note = required_param('note', PARAM_RAW_TRIMMED);

$DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

UserNoteService::add($userid, $note);

redirect(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
    get_string('crm_note_added_successfully', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);