<?php

namespace local_subscriptions\crm\inbox\observer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxUserMatchRepository;
use local_subscriptions\crm\inbox\services\InboxContactReconciliationService;
use local_subscriptions\crm\inbox\services\InboxUserMatcher;

final class InboxUserObserver {

    public static function user_created(
        \core\event\user_created $event
    ): void {
        self::reconcile(
            (int)$event->objectid
        );
    }

    public static function user_updated(
        \core\event\user_updated $event
    ): void {
        self::reconcile(
            (int)$event->objectid
        );
    }

    private static function reconcile(
        int $userid
    ): void {
        $contacts = new InboxContactRepository();
        $matches = new InboxUserMatchRepository();

        $matcher = new InboxUserMatcher(
            $contacts,
            $matches
        );

        $service =
            new InboxContactReconciliationService(
                $contacts,
                $matches,
                $matcher
            );

        try {
            $service->reconcile_user($userid);
        } catch (\Throwable $exception) {
            debugging(
                'CRM Inbox user reconciliation failed: ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}