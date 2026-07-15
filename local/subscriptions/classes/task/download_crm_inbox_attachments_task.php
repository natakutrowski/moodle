<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;
use local_subscriptions\crm\inbox\services\InboxAttachmentService;
use local_subscriptions\crm\inbox\storage\MoodleFileInboxAttachmentStorage;

final class download_crm_inbox_attachments_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_download_crm_inbox_attachments',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $repository =
            new InboxAttachmentRepository();

        $accounts = new InboxAccountRepository();

        $credentials =
            new MoodleConfigInboxCredentialStore();

        $connector = new OvhImapConnector(
            $credentials,
            new ImapMimeParser()
        );

        $service = new InboxAttachmentService(
            $repository,
            new MoodleFileInboxAttachmentStorage(),
            $connector
        );

        foreach (
            $repository->get_pending_with_source(50)
            as $attachment
        ) {
            $account = $accounts->find(
                (int)$attachment->accountid
            );

            if (!$account || !$account->enabled) {
                continue;
            }

            try {
                $repository->reset_pending(
                    (int)$attachment->id
                );

                $service->download(
                    $account,
                    (int)$attachment->id,
                    (string)$attachment->folder,
                    (string)$attachment->provideruid
                );

                mtrace(
                    '[CRM Inbox] Attachment stored: ' .
                    $attachment->filename
                );
            } catch (\Throwable $exception) {
                mtrace(
                    '[CRM Inbox] Attachment failed: ' .
                    $exception->getMessage()
                );
            }
        }
    }
}