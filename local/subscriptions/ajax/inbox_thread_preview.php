<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\rendering\InboxThreadPreviewRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
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
        new InboxTeamRepository()
    );

    $thread = $service->thread(
        $threadid
    );

    $canmanage = AdminSecurity::can(
        Capabilities::MANAGE_INBOX
    );

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