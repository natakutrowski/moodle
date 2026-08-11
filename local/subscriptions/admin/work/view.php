<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\work\rendering\WorkItemRenderer;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::VIEW_WORK_ITEMS
);

$itemid = required_param(
    'id',
    PARAM_INT
);

$repository = new WorkItemReadRepository();
$item = $repository->get_detail($itemid);

$pageurl = new moodle_url(
    subscription_config::admin_work_item_view_page(),
    [
        'id' => $itemid,
    ]
);

/*
 * format_string() peut consulter $PAGE->context.
 * Le contexte doit donc être défini avant le calcul du titre.
 */
$PAGE->set_context($context);

$formattedtitle = format_string(
    $item->title,
    true,
    [
        'context' => $context,
    ]
);

$pagetitle =
    $item->reference .
    ' — ' .
    $formattedtitle;

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-work-page',
        'local-subscriptions-work-view-page',
    ]
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/work_items',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::WORK,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' => get_string(
                'crm_work_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::admin_work_items_page()
            ),
        ],
        [
            'label' => $item->reference,
            'url' => null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::admin_work_items_page()
    ),
    get_string(
        'crm_work_back',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $item->reference,
    $formattedtitle,
    HelpContext::WORK_ITEMS
);

echo WorkItemRenderer::render_detail(
    $item,
    $repository->get_teams(),
    $repository->get_assignees()
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();