<?php

namespace local_subscriptions\dashboard\repositories;

defined('MOODLE_INTERNAL') || die();

final class DashboardStatsRepository {

    public function count_new_users(int $start, int $end): int {
        global $DB;

        return $DB->count_records_select(
            'user',
            'deleted = 0 AND timecreated >= :start AND timecreated < :end',
            ['start' => $start, 'end' => $end]
        );
    }

    public function count_new_subscriptions(int $start, int $end): int {
        global $DB;

        return $DB->count_records_select(
            'user_subscription',
            'creation_date >= :start AND creation_date < :end',
            ['start' => $start, 'end' => $end]
        );
    }

    public function count_digital_purchases(int $start, int $end): int {
        global $DB;

        return $DB->count_records_select(
            'subscription_digital_payment_request',
            'creation_date >= :start AND creation_date < :end',
            ['start' => $start, 'end' => $end]
        );
    }

    public function get_digital_revenue_by_currency(int $start, int $end): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT
                currency,
                SUM(
                    CASE
                        WHEN locked_final_price IS NOT NULL AND locked_final_price > 0
                            THEN locked_final_price
                        ELSE price
                    END
                ) AS total
              FROM {subscription_digital_payment_request}
             WHERE payment_date >= :start
               AND payment_date < :end
               AND status IN ('paid', 'completed', 'PAID', 'COMPLETED')
          GROUP BY currency
          ORDER BY currency ASC
        ", [
            'start' => $start,
            'end' => $end,
        ]));
    }
}