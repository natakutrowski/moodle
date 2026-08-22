<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\dto\InboxFolder;
use local_subscriptions\crm\inbox\dto\InboxRemoteStateSnapshot;
use local_subscriptions\crm\inbox\dto\InboxSyncPage;

interface InboxConnectorInterface {

    public function test_connection(
        InboxAccount $account
    ): void;

    /**
     * @return InboxFolder[]
     */
    public function list_folders(
        InboxAccount $account
    ): array;

    public function fetch_page(
        InboxAccount $account,
        string $folder,
        ?string $cursor,
        int $limit
    ): InboxSyncPage;

    /**
     * Reads lightweight IMAP state for already-known UIDs without fetching
     * message bodies or MIME attachments. Missing UIDs are omitted.
     *
     * @param string[] $provideruids
     */
    public function inspect_messages(
        InboxAccount $account,
        string $folder,
        array $provideruids
    ): InboxRemoteStateSnapshot;

    /**
     * Locates an already-known RFC Message-ID in other IMAP folders.
     *
     * @param string[] $folders
     */
    public function locate_message(
        InboxAccount $account,
        array $folders,
        string $providermessageid
    ): ?\local_subscriptions\crm\inbox\dto\InboxRemoteMessageState;

    public function move_message(
        InboxAccount $account,
        string $sourcefolder,
        string $provideruid,
        string $targetfolder
    ): void;

    public function mark_as_read(
        InboxAccount $account,
        string $folder,
        string $provideruid,
        bool $read
    ): void;
}