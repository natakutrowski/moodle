<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationLifecycleService;
use local_subscriptions\subscription_config;

global $USER;

AdminSecurity::require(
    Capabilities::MANAGE_USERS
);

require_sesskey();

$recommendationid = required_param(
    'recommendationid',
    PARAM_INT
);

$action = required_param(
    'action',
    PARAM_ALPHA
);

$reason = optional_param(
    'reason',
    'not_relevant',
    PARAM_ALPHANUMEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$service =
    new RecommendationLifecycleService();

try {
    switch ($action) {
        case 'accept':
            $service->accept(
                $recommendationid,
                (int)$USER->id
            );

            $message = get_string(
                'crm_assistant_accepted',
                'local_subscriptions'
            );
            break;

        case 'dismiss':
            $service->dismiss(
                $recommendationid,
                (int)$USER->id,
                $reason
            );

            $message = get_string(
                'crm_assistant_dismissed',
                'local_subscriptions'
            );
            break;

        case 'complete':
            $service->complete(
                $recommendationid,
                (int)$USER->id
            );

            $message = get_string(
                'crm_assistant_completed',
                'local_subscriptions'
            );
            break;

        default:
            throw new \InvalidArgumentException(
                'Unsupported CRM Assistant action.'
            );
    }

    $redirecturl = $returnurl !== ''
        ? new moodle_url($returnurl)
        : new moodle_url(
            subscription_config::
                admin_crm_assistant_page()
        );

    redirect(
        $redirecturl,
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (\Throwable $exception) {
    debugging(
        $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    redirect(
        new moodle_url(
            subscription_config::
                admin_crm_assistant_page()
        ),
        get_string(
            'crm_assistant_action_failed',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}