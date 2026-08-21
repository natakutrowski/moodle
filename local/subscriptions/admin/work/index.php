<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\work\dto\WorkItemCriteria;
use local_subscriptions\crm\work\rendering\WorkItemRenderer;
use local_subscriptions\crm\work\rendering\WorkSectionNavigationRenderer;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_WORK_ITEMS
);

$criteria =
    WorkItemCriteria::from_request();

$result = (
    new WorkItemReadRepository()
)->search(
    $criteria,
    (int)$USER->id
);

$pageurl = new moodle_url(
    subscription_config::
        admin_work_items_page(),
    $criteria->url_params()
);

$pagetitle = get_string(
    'crm_work_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-work-page'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/work_items',
    'init'
);

$actions = '';

if (
    AdminSecurity::can(
        Capabilities::MANAGE_WORK_ITEMS
    )
) {
    $actions .= html_writer::link(
        new moodle_url(
            subscription_config::
                admin_work_item_create_page()
        ),
        get_string(
            'crm_work_create',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-primary btn-sm',
        ]
    );
}

if (
    AdminSecurity::can(
        Capabilities::MANAGE_WORK_CONFIGURATION
    )
) {
    $actions .= ' ' .
        html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_work_teams_page()
            ),
            get_string(
                'crm_work_teams',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-outline-secondary btn-sm',
            ]
        );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::WORK,
    $context
);

echo WorkSectionNavigationRenderer::render(
    WorkSectionNavigationRenderer::ITEMS
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_work_subtitle',
        'local_subscriptions'
    ),
    HelpContext::WORK_ITEMS,
    $actions
);

echo WorkItemRenderer::render_list(
    $result
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();