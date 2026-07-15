<?php

namespace local_subscriptions\crm\inbox\sync;

defined('MOODLE_INTERNAL') || die();

final class InboxSyncLock {

    private const LOCKTYPE =
        'local_subscriptions_crm_inbox';

    public function acquire(
        int $accountid,
        int $timeout = 1
    ): \core\lock\lock {
        $factory =
            \core\lock\lock_config::get_lock_factory(
                self::LOCKTYPE
            );

        $lock = $factory->get_lock(
            'account_' . $accountid,
            max(0, $timeout)
        );

        if (!$lock) {
            throw new \RuntimeException(
                'CRM Inbox synchronisation is already running.'
            );
        }

        return $lock;
    }
}