<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;

final class InboxSyncRuntime {

    public function __construct(
        public readonly InboxAccountRepository $accounts,
        public readonly InboxSyncService $sync
    ) {
    }
}