<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadActionRepository;
use local_subscriptions\crm\inbox\services\InboxThreadActionService;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
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

$threadid = required_param(
    'id',
    PARAM_INT
);

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

$value = optional_param(
    'value',
    '',
    PARAM_ALPHANUMEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$credentials =
    new MoodleConfigInboxCredentialStore();

$service = new InboxThreadActionService(
    new InboxReadRepository(),
    new InboxThreadActionRepository(),
    new InboxAccountRepository(),
    new OvhImapConnector(
        $credentials,
        new ImapMimeParser()
    ),
    new InboxRemoteMessageRepository()
);

switch ($action) {
    case 'status':
        $service->set_status(
            $threadid,
            $value
        );
        break;

    case 'priority':
        $service->set_priority(
            $threadid,
            $value
        );
        break;

    case 'read':
        $service->mark_read(
            $threadid,
            true
        );
        break;

    case 'unread':
        $service->mark_read(
            $threadid,
            false
        );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_page()
            ),
            get_string(
                'crm_inbox_marked_unread_o2',
                'local_subscriptions'
            )
        );
        break;

    case 'archive':
        $service->archive($threadid);
        break;

    case 'trash':
        $service->trash($threadid);

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_page()
            ),
            get_string(
                'crm_inbox_moved_to_trash',
                'local_subscriptions'
            )
        );
        break;

    case 'restore':
        $service->restore_to_inbox(
            $threadid
        );

        redirect(
            $returnurl !== ''
                ? new moodle_url($returnurl)
                : new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
            get_string(
                'crm_inbox_restored_o13',
                'local_subscriptions'
            )
        );
        break;

    case 'delete_local':
        $service->delete_locally(
            $threadid
        );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_page()
            ),
            get_string(
                'crm_inbox_deleted_locally',
                'local_subscriptions'
            )
        );
        break;

    default:
        throw new invalid_parameter_exception(
            'Unknown Inbox action.'
        );
}

redirect(
    new moodle_url(
        subscription_config::
            admin_inbox_thread_page(),
        ['id' => $threadid]
    ),
    get_string(
        'changessaved',
        'core'
    )
);