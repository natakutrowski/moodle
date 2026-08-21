<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\services\InboxManualSyncService;
use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;
use local_subscriptions\subscription_config;

AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

require_sesskey();

$returnurl = optional_param(
    'returnurl',
    subscription_config::admin_inbox_page(),
    PARAM_LOCALURL
);

if ($returnurl === '') {
    $returnurl =
        subscription_config::admin_inbox_page();
}

try {
    $runtime = (
        new InboxSyncRuntimeFactory()
    )->create_runtime();

    $summary = (
        new InboxManualSyncService(
            $runtime->accounts,
            $runtime->sync
        )
    )->sync_enabled_accounts();

    if ((int)$summary['accountcount'] === 0) {
        redirect(
            new moodle_url($returnurl),
            get_string(
                'crm_inbox_refresh_no_accounts',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $message = get_string(
        'crm_inbox_refresh_success',
        'local_subscriptions',
        (object)[
            'fetched' => (int)$summary['fetched'],
            'created' => (int)$summary['created'],
            'updated' => (int)$summary['updated'],
            'errors' => (int)$summary['errors'],
        ]
    );

    if (!empty($summary['hasmore'])) {
        $message .= ' ' . get_string(
            'crm_inbox_refresh_has_more',
            'local_subscriptions'
        );
    }

    redirect(
        new moodle_url($returnurl),
        $message,
        null,
        (int)$summary['errors'] > 0
            ? \core\output\notification::NOTIFY_WARNING
            : \core\output\notification::NOTIFY_SUCCESS
    );
} catch (\Throwable $exception) {
    debugging(
        'Manual CRM Inbox refresh failed: '
        . $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    redirect(
        new moodle_url($returnurl),
        get_string(
            'crm_inbox_refresh_failed',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
