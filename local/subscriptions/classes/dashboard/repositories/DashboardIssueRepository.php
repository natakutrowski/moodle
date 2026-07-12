<?php

namespace local_subscriptions\dashboard\repositories;

defined('MOODLE_INTERNAL') || die();

final class DashboardIssueRepository {

    private const TABLE = 'subscription_digital_payment_request';

    public function count_pending_digital_payments(): int {
        global $DB;

        return $DB->count_records_select(
            self::TABLE,
            'LOWER(status) = :status',
            [
                'status' => 'pending',
            ]
        );
    }

    public function count_failed_digital_payments(): int {
        global $DB;

        return $DB->count_records_select(
            self::TABLE,
            'LOWER(status) = :status',
            [
                'status' => 'failed',
            ]
        );
    }

    public function count_paid_email_errors(): int {
        global $DB;

        return $DB->count_records_select(
            self::TABLE,
            "
                LOWER(status) IN (:paid, :completed)
                AND last_error IS NOT NULL
                AND last_error <> ''
            ",
            [
                'paid' => 'paid',
                'completed' => 'completed',
            ]
        );
    }

    public function count_expired_paid_download_tokens(): int {
        global $DB;

        return $DB->count_records_select(
            self::TABLE,
            "
                LOWER(status) IN (:paid, :completed)
                AND download_token IS NOT NULL
                AND download_token <> ''
                AND download_token_expires IS NOT NULL
                AND download_token_expires > 0
                AND download_token_expires < :now
            ",
            [
                'paid' => 'paid',
                'completed' => 'completed',
                'now' => time(),
            ]
        );
    }
}