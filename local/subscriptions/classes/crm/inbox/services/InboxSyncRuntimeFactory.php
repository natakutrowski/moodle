<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxParticipantRepository;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxSyncLogRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;
use local_subscriptions\crm\inbox\repositories\InboxUserMatchRepository;
use local_subscriptions\crm\inbox\sync\InboxSyncLock;

final class InboxSyncRuntimeFactory {

    public function create_runtime():
        InboxSyncRuntime {
        $accounts =
            new InboxAccountRepository();

        $contacts =
            new InboxContactRepository();

        $matcher =
            new InboxUserMatcher(
                $contacts,
                new InboxUserMatchRepository()
            );

        $connector = new OvhImapConnector(
            new MoodleConfigInboxCredentialStore(),
            new ImapMimeParser()
        );

        $discovery = new InboxFolderDiscoveryService(
            $connector,
            new InboxFolderResolver(),
            $accounts
        );

        $sync = new InboxSyncService(
            $connector,
            $accounts,
            $contacts,
            new InboxThreadRepository(),
            new InboxMessageRepository(),
            new InboxParticipantRepository(),
            new InboxAttachmentRepository(),
            new InboxSyncLogRepository(),
            new InboxSyncLock(),
            new InboxRemoteMessageRepository(),
            $matcher
        );

        return new InboxSyncRuntime(
            $accounts,
            $sync,
            $discovery
        );
    }

    public function create():
        InboxSyncService {
        return $this->create_runtime()->sync;
    }
}