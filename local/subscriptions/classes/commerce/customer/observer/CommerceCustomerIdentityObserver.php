<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\observer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

/** Reconciles account-less Commerce ownership when a Moodle identity appears or changes. */
final class CommerceCustomerIdentityObserver {
    public static function user_created(\core\event\user_created $event): void {
        self::reconcile((int)$event->objectid);
    }

    public static function user_updated(\core\event\user_updated $event): void {
        self::reconcile((int)$event->objectid);
    }

    private static function reconcile(int $userid): void {
        global $DB;

        try {
            (new CommerceCustomerIdentityReconciliationService($DB))->reconcile_user($userid, true);
        } catch (\Throwable $exception) {
            debugging(
                'Commerce customer identity reconciliation failed for user #' . $userid . ': ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}
