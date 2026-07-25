<?php
namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

final class LoginTokenCleanupRepository {
    public function count_expired(int $now): int {
        global $DB;

        return $DB->count_records_select(
            'subscription_payment_request',
            'login_token_expires IS NOT NULL AND login_token_expires < :now',
            ['now' => $now]
        );
    }

    public function clear_expired(int $now): void {
        global $DB;

        $DB->execute(
            "UPDATE {subscription_payment_request}
                SET login_token = NULL,
                    login_token_expires = NULL,
                    last_update = :updatedat
              WHERE login_token_expires IS NOT NULL
                AND login_token_expires < :expiresbefore",
            [
                'updatedat' => $now,
                'expiresbefore' => $now,
            ],
        );
    }
}
