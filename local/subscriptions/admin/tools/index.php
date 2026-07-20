<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\admin_tools\rendering\AdminToolRenderer;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CRM_ADMIN_TOOLS
);

$pageurl = new moodle_url(
    subscription_config::admin_crm_tools_page()
);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_admin_tools_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_admin_tools_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-admin-tools-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$registry = new AdminToolRegistry();
$repository = new AdminToolRunRepository();

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
    get_string(
        'crm_admin_tools_title',
        'local_subscriptions'
    ),
    get_string(
        'crm_admin_tools_description',
        'local_subscriptions'
    ),
    HelpContext::DASHBOARD,
    $actions
);

echo AdminToolRenderer::render_catalogue(
    $registry->visible($context),
    $repository
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();