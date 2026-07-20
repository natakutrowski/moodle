<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerRenderer;
use local_subscriptions\crm\user\explorer\UserExplorerService;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

$canviewinbox = AdminSecurity::can(
    Capabilities::VIEW_INBOX
);

$criteria =
    UserExplorerCriteria::from_request();

if (!$canviewinbox) {
    $criteria =
        $criteria->without_inbox();
}

$url = new moodle_url(
    subscription_config::admin_users_page(),
    $criteria->url_params()
);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(
    get_string(
        'crm_users',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_users',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-user-explorer-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::
            plugin_stylesheet_page()
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

$result =
    (new UserExplorerService())
        ->explore(
            $criteria,
            $canviewinbox
        );

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::USERS,
    $context
);

echo CrmPageHeader::render(
    get_string(
        'crm_users',
        'local_subscriptions'
    ),
    get_string(
        'crm_users_explorer_description',
        'local_subscriptions'
    ),
    HelpContext::USER_EXPLORER
);

echo UserExplorerRenderer::render(
    $result
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();