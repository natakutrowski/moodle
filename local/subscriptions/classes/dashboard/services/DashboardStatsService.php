<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminFormatter;

final class DashboardStatsService {

    public static function load_today(): \stdClass {
        global $DB;

        $start = strtotime('today');
        $end = $start + DAYSECS;

        $stats = new \stdClass();

        $stats->newusers = $DB->count_records_select(
            'user',
            'deleted = 0 AND timecreated >= :start AND timecreated < :end',
            ['start' => $start, 'end' => $end]
        );

        $stats->newsubscriptions = $DB->count_records_select(
            'user_subscription',
            'creation_date >= :start AND creation_date < :end',
            ['start' => $start, 'end' => $end]
        );

        $stats->digitalpurchases = $DB->count_records_select(
            'subscription_digital_payment_request',
            'creation_date >= :start AND creation_date < :end',
            ['start' => $start, 'end' => $end]
        );

        $revenues = $DB->get_records_sql("
            SELECT currency, SUM(price) AS total
              FROM {subscription_digital_payment_request}
             WHERE payment_date >= :start
               AND payment_date < :end
               AND status IN ('paid', 'completed', 'PAID', 'COMPLETED')
          GROUP BY currency
          ORDER BY currency ASC
        ", ['start' => $start, 'end' => $end]);

        if (!$revenues) {
            $stats->revenue = '-';
        } else {
            $parts = [];

            foreach ($revenues as $revenue) {
                $parts[] = AdminFormatter::price($revenue->total ?? 0, $revenue->currency ?? '');
            }

            $stats->revenue = implode('<br>', $parts);
        }

        return $stats;
    }
}