<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\guides\HelpGuideService;
use local_subscriptions\crm\help\guides\HelpGuideRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$guideid = required_param(
    'id',
    PARAM_ALPHANUMEXT
);

$url = new moodle_url(
    subscription_config::admin_help_guide_page(),
    ['id' => $guideid]
);

$pagetitle = get_string(
    'crm_help_guides_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    [
        'local-subscriptions-help-page',
        'local-subscriptions-help-guide-page',
    ]
);

$state = (new HelpGuideService())->get_state(
    (int)$USER->id,
    $guideid
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::HELP,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_help_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_help_page()
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
    new moodle_url(
        subscription_config::
            admin_help_page()
    ),
    get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'crm-help-back-link',
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_help_guides_description',
        'local_subscriptions'
    ),
    HelpContext::HELP_CENTER
);

echo HelpGuideRenderer::render_guide(
    $state,
    $url->out_as_local_url(false)
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();