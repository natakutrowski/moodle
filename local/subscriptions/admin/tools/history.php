<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\rendering\AdminToolRenderer;
use local_subscriptions\crm\admin_tools\repositories\AdminToolActorRepository;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
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

$pagetitle = get_string(
    'crm_admin_tool_history',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-admin-tools-page',
        'local-subscriptions-admin-tools-history-page',
    ]
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

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_admin_tools_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_crm_tools_page()
                ),
        ],
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    $returnurl,
    get_string(
        'crm_admin_tools_title',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_admin_tool_history_subtitle',
        'local_subscriptions'
    ),
    HelpContext::ADMIN_TOOLS
);

echo AdminToolRenderer::render_history(
    $runs,
    $registry,
    $actorrepository
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();