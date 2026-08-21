<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\ai\services\InboxAiPanelService;
use local_subscriptions\crm\inbox\ai\services\InboxAiRuntimeFactory;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

AdminSecurity::require(
    Capabilities::USE_INBOX_AI
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception(
        'invalidrequest',
        'error'
    );
}

require_sesskey();

$threadid = required_param(
    'threadid',
    PARAM_INT
);

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

$language = optional_param(
    'language',
    'fr',
    PARAM_ALPHA
);

$tone = optional_param(
    'tone',
    'professional',
    PARAM_ALPHANUMEXT
);

$force = optional_param(
    'force',
    0,
    PARAM_BOOL
) === 1;

if (
    !in_array(
        $language,
        ['fr', 'en', 'ru'],
        true
    )
) {
    $language = 'fr';
}

$service = new InboxAiPanelService(
    new InboxAiRuntimeFactory()
);

$quota = new \local_subscriptions\crm\inbox\ai\services\InboxAiQuotaService(
    new \local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository()
);

switch ($action) {
    case 'translate':
        $quota->assert_can_consume(
            (int)$USER->id,
            1
        );

        $result = $service->translate(
            $threadid,
            $language,
            (int)$USER->id,
            $force
        );
        break;

    case 'analyse':
        /*
         * Une analyse complète peut appeler :
         *
         * - language detection ;
         * - urgency classification ;
         * - categorization ;
         * - summary.
         */
        $quota->assert_can_consume(
            (int)$USER->id,
            4
        );

        $result = $service->analyse(
            $threadid,
            $language,
            (int)$USER->id,
            $force
        );
        break;

    case 'reply':
        AdminSecurity::require(
            Capabilities::MANAGE_INBOX
        );

        $quota->assert_can_consume(
            (int)$USER->id,
            1
        );

        $result = $service->suggest_reply(
            $threadid,
            $language,
            $tone,
            (int)$USER->id,
            $force
        );
        break;

    default:
        throw new invalid_parameter_exception(
            'Unknown Inbox AI action.'
        );
}

if (
    !isset(
        $SESSION->local_subscriptions_inbox_ai
    ) ||
    !is_array(
        $SESSION->local_subscriptions_inbox_ai
    )
) {
    $SESSION->local_subscriptions_inbox_ai =
        [];
}

$SESSION->local_subscriptions_inbox_ai[
    $threadid
] = $result;

$redirecturl = new moodle_url(
    subscription_config::
        admin_inbox_thread_page(),
    ['id' => $threadid]
);

$redirecturl->set_anchor(
    'crm-inbox-ai'
);

redirect(
    $redirecturl,
    get_string(
        'crm_inbox_ai_analysis_completed',
        'local_subscriptions'
    ),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
