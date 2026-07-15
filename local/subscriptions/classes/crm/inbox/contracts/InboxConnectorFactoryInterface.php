<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;

interface InboxConnectorFactoryInterface {

    public function inbound(
        InboxAccount $account
    ): InboxConnectorInterface;

    public function outbound(
        InboxAccount $account
    ): InboxOutboundConnectorInterface;
}