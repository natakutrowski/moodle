<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\inbox\workspace\InboxThreadWorkspaceRenderer;
use local_subscriptions\crm\inbox\rendering\InboxThreadRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadActionRepository;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxThreadActionService;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;

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
    new InboxTeamRepository(),
        new InboxAccountRepository()
);

$thread = $service->thread($threadid);

$canmanage = AdminSecurity::can(
    Capabilities::MANAGE_INBOX
);

/*
 * Standard mailbox behaviour: opening an unread conversation acknowledges
 * its inbound mail. The state is pushed to IMAP first so Roundcube/
 * Thunderbird and CampusFR converge on the same \Seen flags.
 */
if (
    $canmanage
    && (int)($thread->unreadcount ?? 0) > 0
) {
    try {
        $actions = new InboxThreadActionService(
            new InboxReadRepository(),
            new InboxThreadActionRepository(),
            new InboxAccountRepository(),
            new OvhImapConnector(
                new MoodleConfigInboxCredentialStore(),
                new ImapMimeParser()
            ),
            new InboxRemoteMessageRepository()
        );

        $actions->mark_read(
            $threadid,
            true
        );

        $thread = $service->thread(
            $threadid
        );
    } catch (\Throwable $exception) {
        debugging(
            'CRM Inbox automatic read acknowledgement failed: ' .
            $exception->getMessage(),
            DEBUG_DEVELOPER
        );
    }
}

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

$headeractions = '';

$threadhasdraft =
    InboxThreadRenderer::thread_has_draft(
        $thread
    );

if ($canmanage && $threadhasdraft) {
    $drafturl = strtoupper(
        trim((string)($thread->folder ?? ''))
    ) === 'DRAFTS'
        ? new moodle_url(
            subscription_config::admin_inbox_compose_page(),
            ['threadid' => $threadid]
        )
        : new moodle_url(
            subscription_config::admin_inbox_reply_page(),
            [
                'threadid' => $threadid,
                'mode' => 'reply',
            ]
        );

    $headeractions = html_writer::link(
        $drafturl,
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-pencil',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_resume_draft_o7',
                'local_subscriptions'
            )
        ),
        [
            'class' =>
                'btn btn-primary '
                . 'crm-inbox-thread-header-resume-draft',
        ]
    );
} elseif ($canmanage) {
    $headeractions = html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_reply_page(),
            [
                'threadid' => $threadid,
                'mode' => 'reply',
            ]
        ),
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-reply me-1',
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

    $headeractions .= html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_reply_page(),
            [
                'threadid' => $threadid,
                'mode' => 'replyall',
            ]
        ),
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-reply-all me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_reply_all_o6',
                'local_subscriptions'
            )
        ),
        [
            'class' =>
                'btn btn-outline-secondary '
                . 'crm-inbox-thread-header-replyall',
        ]
    );

    $headeractions .= html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_reply_page(),
            [
                'threadid' => $threadid,
                'mode' => 'forward',
            ]
        ),
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-share me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_forward_o6',
                'local_subscriptions'
            )
        ),
        [
            'class' =>
                'btn btn-outline-secondary '
                . 'crm-inbox-thread-header-forward',
        ]
    );

    $headeractions .= html_writer::tag(
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
                'name' => 'id',
                'value' => $threadid,
            ]
        )
        . html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'action',
                'value' => 'unread',
            ]
        )
        . html_writer::tag(
            'button',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'fa fa-envelope me-1',
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_mark_unread_o2',
                    'local_subscriptions'
                )
            ),
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-outline-secondary ' .
                    'crm-inbox-thread-header-unread',
            ]
        ),
        [
            'method' => 'post',
            'action' => (
                new moodle_url(
                    subscription_config::
                        admin_inbox_action_page()
                )
            )->out(false),
            'class' => 'd-inline-block m-0',
        ]
    );
}

$headeractions .= html_writer::tag(
    'button',
    html_writer::tag(
        'i',
        '',
        [
            'class' => 'fa fa-cog',
            'aria-hidden' => 'true',
        ]
    )
    . html_writer::span(
        get_string(
            'inbox_workspace_personalization_open',
            'local_subscriptions'
        )
    ),
    [
        'type' => 'button',
        'class' =>
            'btn btn-outline-secondary '
            . 'crm-inbox-thread-header-personalize',
        'data-action' =>
            'open-workspace-personalization-proxy',
        'data-workspace' =>
            'inbox_thread',
    ]
);

echo CrmPageHeader::render(
    $threadtitle,
    get_string(
        'crm_inbox_thread_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_AI,
    $headeractions
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::INBOX
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