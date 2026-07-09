<?php

namespace local_subscriptions\crm\intelligence\reports;

defined('MOODLE_INTERNAL') || die();

final class CrmFunnelRepository {

    public function build_report(): CrmFunnelReport {
        global $DB;

        $users = (int)$DB->count_records_select('user', 'deleted = 0 AND id > 2');

        $trials = (int)$DB->count_records_select(
            'user_subscription',
            "LOWER(status) = :status",
            ['status' => 'trial']
        );

        $customers = (int)$DB->count_records_select(
            'user_subscription',
            "LOWER(status) IN ('active', 'expired', 'cancelled', 'replaced')"
        );

        $expired = (int)$DB->count_records_select(
            'user_subscription',
            "LOWER(status) = :status",
            ['status' => 'expired']
        );

        $digitalcustomers = 0;

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $digitalcustomers = (int)$DB->count_records_select(
                'subscription_digital_payment_request',
                "UPPER(status) IN ('PAID', 'COMPLETED')"
            );
        }

        return new CrmFunnelReport(
            $users,
            $trials,
            $customers,
            $digitalcustomers,
            $expired
        );
    }
}