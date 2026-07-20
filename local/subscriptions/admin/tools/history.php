<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\rendering\AdminToolRenderer;
use local_subscriptions\crm\admin_tools\repositories\AdminToolActorRepository;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CRM_ADMIN_TOOLS
);

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_tool_history_page()
);

$returnurl = new moodle_url(
    subscription_config::
        admin_crm_tools_page()
);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_admin_tool_history',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_admin_tool_history',
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

$runs =
    (new AdminToolRunRepository())
        ->recent(100);

$registry =
    new AdminToolRegistry();

$actorrepository =
    new AdminToolActorRepository();

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::TOOLS,
    $context
);

echo html_writer::link(
    $returnurl,
    '← ' . get_string(
        'crm_admin_tools_title',
        'local_subscriptions'
    ),
    [
        'class' =>
            'crm-app-back-link btn btn-link ps-0 mb-3',
    ]
);

echo $OUTPUT->heading(
    get_string(
        'crm_admin_tool_history',
        'local_subscriptions'
    ),
    2
);

echo AdminToolRenderer::render_history(
    $runs,
    $registry,
    $actorrepository
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();