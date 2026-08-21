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
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\HelpInternalNavigationRenderer;

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

$state = (new HelpGuideService())->get_state(
    (int)$USER->id,
    $guideid
);

$pagetitle = $state->guide->title;

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
            'label' => get_string(
                'crm_help_guides_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    admin_help_page(),
                ['section' => 'guides']
            ),
        ],
        [
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    $state->guide->description,
    HelpContext::HELP_CENTER
);

echo HelpInternalNavigationRenderer::render(
    'guides'
);

echo HelpGuideRenderer::render_guide(
    $state,
    $url->out_as_local_url(false)
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();