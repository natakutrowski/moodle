<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\AdminNavigation;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserProfileService;
use local_subscriptions\output\UserProfileRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$id = required_param('id', PARAM_INT);

$url = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]);

$profile = UserProfileService::load($id);

$user = $profile->user;

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('crm_user_profile', 'local_subscriptions') . ' - ' . fullname($user));
$PAGE->set_heading(get_string('crm_user_profile', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

echo $OUTPUT->header();

echo AdminNavigation::back_button();

echo html_writer::link(
    new moodle_url(subscription_config::admin_users_page()),
    '← ' . get_string('back'),
    ['class' => 'btn btn-outline-secondary mb-3 ms-2']
);

echo CrmPageHeader::render(
    get_string(
        'crm_user_profile',
        'local_subscriptions'
    ),
    get_string(
        'crm_user_profile_help_description',
        'local_subscriptions'
    ),
    HelpContext::USER_PROFILE
);

echo UserProfileRenderer::render($profile);

echo $OUTPUT->footer();