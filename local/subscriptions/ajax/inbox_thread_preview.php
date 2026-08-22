<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\rendering\InboxThreadPreviewRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\inbox\services\InboxThreadActionService;
use local_subscriptions\crm\inbox\services\InboxUnreadCountService;
use local_subscriptions\crm\inbox\repositories\InboxThreadActionRepository;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\subscription_config;

global $USER;

header(
    'Content-Type: application/json; charset=utf-8'
);

try {
    AdminSecurity::require(
        Capabilities::VIEW_INBOX
    );

    require_sesskey();

    $threadid = required_param(
        'threadid',
        PARAM_INT
    );

    $loadimages = optional_param(
        'loadimages',
        0,
        PARAM_BOOL
    );

    $service = new InboxReadService(
        new InboxReadRepository(),
        new InboxTeamRepository(),
        new InboxAccountRepository()
    );

    $thread = $service->thread(
        $threadid
    );

    $canmanage = AdminSecurity::can(
        Capabilities::MANAGE_INBOX
    );

    $markedread = false;

    /*
     * O18: selecting an unread conversation for preview is a genuine read
     * action, just like opening thread.php. Keep IMAP/Roundcube and the CRM
     * database converged by reusing the canonical thread action service.
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

            $markedread = true;
            $thread = $service->thread(
                $threadid
            );
        } catch (\Throwable $exception) {
            debugging(
                'CRM Inbox preview read acknowledgement failed: ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    $unreadcount = (new InboxUnreadCountService())->count();

    $threadtitle =
        trim(
            (string)($thread->subject ?? '')
        );

    if ($threadtitle === '') {
        $threadtitle = get_string(
            'crm_inbox_no_subject',
            'local_subscriptions'
        );
    }

    $threadurl = new moodle_url(
        subscription_config::
            admin_inbox_thread_page(),
        [
            'id' => $threadid,
        ]
    );

    echo json_encode(
        [
            'success' => true,

            'threadid' =>
                $threadid,

            'title' =>
                $threadtitle,

            'readinghtml' =>
                InboxThreadPreviewRenderer::
                    render_reading(
                        $thread,
                        (bool)$loadimages
                    ),

            'contexthtml' =>
                InboxThreadPreviewRenderer::
                    render_context(
                        $thread,
                        $canmanage
                    ),

            'threadurl' =>
                $threadurl->out(false),

            'markedread' =>
                $markedread,

            'threadunreadcount' =>
                (int)($thread->unreadcount ?? 0),

            'unreadcount' =>
                $unreadcount,

            'unreadlabel' =>
                $unreadcount > 0
                    ? get_string(
                        'crm_nav_inbox_unread_badge_o3',
                        'local_subscriptions',
                        $unreadcount
                    )
                    : '',

            'announcement' =>
                get_string(
                    'crm_inbox_preview_loaded',
                    'local_subscriptions',
                    $threadtitle
                ),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    debugging(
        $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    http_response_code(400);

    echo json_encode(
        [
            'success' => false,

            'message' =>
                get_string(
                    'crm_inbox_preview_error',
                    'local_subscriptions'
                ),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}