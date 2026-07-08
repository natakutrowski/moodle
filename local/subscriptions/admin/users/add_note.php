<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\UserProfileNoteService;
use local_subscriptions\crm\user\UserProfileRepository;

global $DB, $USER;

AdminSecurity::require(Capabilities::MANAGE_USERS);
require_sesskey();

$userid = required_param('id', PARAM_INT);
$note = required_param('note', PARAM_RAW_TRIMMED);
$type = optional_param('type', 'general', PARAM_ALPHAEXT);

$DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

$noteservice = new UserProfileNoteService(new UserProfileRepository());
$noteservice->add($userid, (int)$USER->id, $note, $type);

redirect(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
    get_string('crm_note_added_successfully', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);