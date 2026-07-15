<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\dto\InboxReplyRequest;
use local_subscriptions\crm\inbox\dto\InboxSendResult;

interface InboxOutboundConnectorInterface {

    public function test_connection(
        InboxAccount $account
    ): void;

    public function send(
        InboxAccount $account,
        InboxReplyRequest $request
    ): InboxSendResult;
}