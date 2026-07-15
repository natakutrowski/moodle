<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;

interface InboxAttachmentFetcherInterface {

    /**
     * @return resource|string
     */
    public function fetch_attachment(
        InboxAccount $account,
        string $folder,
        string $provideruid,
        string $providerattachmentid
    ): mixed;
}