<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\inbox\dto\InboxThreadCriteria;
use local_subscriptions\crm\inbox\workspace\InboxWorkspaceRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

$criteria =
    InboxThreadCriteria::from_request();

$service = new InboxReadService(
    new InboxReadRepository(),
    new InboxTeamRepository()
);

$result = $service->search(
    $criteria,
    (int)$USER->id
);

$pageurl = new moodle_url(
    subscription_config::admin_inbox_page(),
    $criteria->url_params()
);

$pagetitle = get_string(
    'crm_inbox_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-inbox-page'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::INBOX,
    $context
);

$headeractions = [];

if (
    AdminSecurity::can(
        Capabilities::MANAGE_INBOX
    )
) {
    $headeractions[] = html_writer::tag(
        'form',
        html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
            ]
        )
        . html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'returnurl',
                'value' => $pageurl->out(false),
            ]
        )
        . html_writer::tag(
            'button',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'fa fa-refresh',
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_refresh',
                    'local_subscriptions'
                )
            ),
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-sm btn-primary '
                    . 'crm-inbox-refresh-button',
            ]
        ),
        [
            'method' => 'post',
            'action' => (
                new moodle_url(
                    subscription_config::
                        admin_inbox_sync_page()
                )
            )->out(false),
            'class' => 'd-inline-block m-0',
        ]
    );
}

if (
    AdminSecurity::can(
        Capabilities::MANAGE_CONFIGURATION
    )
) {
    $headeractions[] = html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_diagnostics_page()
        ),
        get_string(
            'crm_inbox_diagnostics',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-sm btn-outline-secondary',
        ]
    );
}

$headeractions = html_writer::div(
    implode('', $headeractions),
    'crm-inbox-header-actions'
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_inbox_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX,
    $headeractions
);

echo InboxWorkspaceRenderer::render(
    $result,
    (int)$USER->id
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();