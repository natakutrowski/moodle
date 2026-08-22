<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadActionRepository;
use local_subscriptions\crm\inbox\services\InboxThreadActionService;
use local_subscriptions\crm\inbox\services\InboxBulkActionService;
use local_subscriptions\subscription_config;

AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new \moodle_exception(
        'invalidrequest',
        'error'
    );
}

require_sesskey();

$threadids = optional_param_array(
    'threadids',
    [],
    PARAM_INT
);

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$threadids = array_values(
    array_unique(
        array_filter(
            array_map('intval', $threadids),
            static fn(int $id): bool => $id > 0
        )
    )
);

if ($threadids === []) {
    redirect(
        new moodle_url(
            subscription_config::admin_inbox_page()
        ),
        get_string(
            'crm_inbox_bulk_none_selected_o2',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$threadactions = new InboxThreadActionService(
    new InboxReadRepository(),
    new InboxThreadActionRepository(),
    new InboxAccountRepository(),
    new OvhImapConnector(
        new MoodleConfigInboxCredentialStore(),
        new ImapMimeParser()
    ),
    new InboxRemoteMessageRepository()
);

$bulk = new InboxBulkActionService(
    $threadactions
);

$result = $bulk->execute(
    $threadids,
    $action
);

$destination =
    $returnurl !== ''
        ? new moodle_url($returnurl)
        : new moodle_url(
            subscription_config::
                admin_inbox_page()
        );

if ($result['failed'] > 0) {
    redirect(
        $destination,
        get_string(
            'crm_inbox_bulk_partial_o12',
            'local_subscriptions',
            (object)[
                'success' =>
                    $result['succeeded'],
                'failed' =>
                    $result['failed'],
            ]
        ),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

redirect(
    $destination,
    get_string(
        'crm_inbox_bulk_success_o12',
        'local_subscriptions',
        $result['succeeded']
    ),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
