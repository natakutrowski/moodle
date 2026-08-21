<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\inbox\workspace\InboxThreadWorkspaceRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT, $SESSION, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

$threadid = required_param(
    'id',
    PARAM_INT
);

$allowremoteimages = optional_param(
    'loadimages',
    0,
    PARAM_BOOL
) === 1;

$service = new InboxReadService(
    new InboxReadRepository(),
    new InboxTeamRepository()
);

$thread = $service->thread($threadid);

$canmanage = AdminSecurity::can(
    Capabilities::MANAGE_INBOX
);

$canuseai = AdminSecurity::can(
    Capabilities::USE_INBOX_AI
);

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_thread_page(),
    [
        'id' => $threadid,
    ]
);

$threadtitle =
    trim((string)$thread->subject) !== ''
        ? (string)$thread->subject
        : get_string(
            'crm_inbox_no_subject',
            'local_subscriptions'
        );

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $threadtitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-thread-page',
    ]
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_edit_mode',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_drag_drop',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_item_menu',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/workspace_personalization',
    'init'
);

$backurl = new moodle_url(
    subscription_config::
        admin_inbox_page()
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::INBOX,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_inbox_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
        ],
        [
            'label' =>
                $threadtitle,

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    $backurl,
    get_string(
        'crm_inbox_back',
        'local_subscriptions'
    )
);

$headeractions = '';

if ($canmanage) {
    $headeractions = html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_reply_page(),
            [
                'threadid' => $threadid,
            ]
        ),
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-reply',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_reply',
                'local_subscriptions'
            )
        ),
        [
            'class' =>
                'btn btn-primary '
                . 'crm-inbox-thread-header-reply',
        ]
    );
}

echo CrmPageHeader::render(
    $threadtitle,
    get_string(
        'crm_inbox_thread_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_AI,
    $headeractions
);

$airesult = null;

if (
    isset(
        $SESSION->
            local_subscriptions_inbox_ai[
                $threadid
            ]
    ) &&
    is_array(
        $SESSION->
            local_subscriptions_inbox_ai[
                $threadid
            ]
    )
) {
    $airesult =
        $SESSION->
            local_subscriptions_inbox_ai[
                $threadid
            ];

    unset(
        $SESSION->
            local_subscriptions_inbox_ai[
                $threadid
            ]
    );
}

echo InboxThreadWorkspaceRenderer::render(
    $thread,
    $canmanage,
    $canuseai,
    $airesult,
    (int)$USER->id,
    $allowremoteimages
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();