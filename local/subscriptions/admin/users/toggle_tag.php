<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\UserProfileRepository;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\subscription_config;

$userid = required_param('id', PARAM_INT);
$tag = required_param('tag', PARAM_ALPHAEXT);
$action = required_param('action', PARAM_ALPHA);

require_login();
AdminSecurity::require(Capabilities::MANAGE_USERS);
require_sesskey();

global $USER;

$service = new UserProfileTagService(new UserProfileRepository());

if ($action === 'add') {
    $service->add($userid, $tag, (int)$USER->id);
} else if ($action === 'remove') {
    $service->remove($userid, $tag);
}

redirect(new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]));