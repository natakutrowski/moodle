<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\admin_tools\rendering\AdminToolRenderer;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CRM_ADMIN_TOOLS
);

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_tools_page()
);

$pagetitle = get_string(
    'crm_admin_tools_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-admin-tools-page'
);

$registry =
    new AdminToolRegistry();

$repository =
    new AdminToolRunRepository();

$historyurl = new moodle_url(
    subscription_config::
        admin_crm_tool_history_page()
);

$actions = html_writer::link(
    $historyurl,
    get_string(
        'crm_admin_tool_history',
        'local_subscriptions'
    ),
    [
        'class' =>
            'btn btn-outline-secondary',
    ]
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::TOOLS,
    $context
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_admin_tools_description',
        'local_subscriptions'
    ),
    HelpContext::ADMIN_TOOLS,
    $actions
);

echo AdminToolRenderer::render_catalogue(
    $registry->visible(
        $context
    ),
    $repository
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();