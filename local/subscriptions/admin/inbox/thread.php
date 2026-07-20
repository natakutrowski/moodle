<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\inbox\ai\rendering\InboxAiPanelRenderer;
use local_subscriptions\crm\inbox\rendering\InboxThreadRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT, $SESSION;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

$threadid = required_param(
    'id',
    PARAM_INT
);

$service = new InboxReadService(
    new InboxReadRepository(),
    new InboxTeamRepository()
);

$thread = $service->thread($threadid);

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

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($threadtitle);
$PAGE->set_heading(
    get_string(
        'crm_inbox_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-inbox-page'
);
$PAGE->add_body_class(
    'local-subscriptions-inbox-thread-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::
            plugin_stylesheet_page()
    )
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

echo html_writer::link(
    new moodle_url(
        subscription_config::
            admin_inbox_page()
    ),
    '← ' . get_string(
        'crm_inbox_back',
        'local_subscriptions'
    ),
    [
        'class' =>
            'btn btn-link ps-0 mb-3',
    ]
);

echo CrmPageHeader::render(
    $threadtitle,
    get_string(
        'crm_inbox_thread_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_AI
);

echo InboxThreadRenderer::render(
    $thread,
    AdminSecurity::can(
        Capabilities::MANAGE_INBOX
    )
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

echo InboxAiPanelRenderer::render(
    $threadid,
    $airesult,
    AdminSecurity::can(
        Capabilities::USE_INBOX_AI
    ),
    AdminSecurity::can(
        Capabilities::MANAGE_INBOX
    )
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();