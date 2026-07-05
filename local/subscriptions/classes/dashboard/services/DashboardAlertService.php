<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

final class DashboardAlertService {

    public static function load(): \stdClass {
        global $DB;

        $data = new \stdClass();

        $data->pendingdigital = $DB->count_records_select(
            'subscription_digital_payment_request',
            "status = :status",
            ['status' => 'pending']
        );

        $data->faileddigital = $DB->count_records_select(
            'subscription_digital_payment_request',
            "status IN ('failed', 'FAILED')"
        );

        $data->emailerrors = $DB->count_records_select(
            'subscription_digital_payment_request',
            "last_error IS NOT NULL AND last_error <> ''"
        );

        $data->expiredtokens = $DB->count_records_select(
            'subscription_digital_payment_request',
            "download_token IS NOT NULL
             AND download_token <> ''
             AND download_token_expires IS NOT NULL
             AND download_token_expires > 0
             AND download_token_expires < :now",
            ['now' => time()]
        );

        return $data;
    }
}