<?php

namespace local_subscriptions\dashboard\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\business\CrmBusinessSql;
use local_subscriptions\currency\Currency;
use local_subscriptions\dashboard\value\DashboardRevenue;
use local_subscriptions\commerce\customer\analytics\CommerceCustomerAnalyticsRepository;

/**
 * Dashboard statistical queries.
 */
final class DashboardStatsRepository {

    public function __construct(
        private readonly ?CommerceCustomerAnalyticsRepository $nativeanalytics = null
    ) {
    }

    private function native_analytics(): CommerceCustomerAnalyticsRepository {
        global $DB;
        return $this->nativeanalytics ?? new CommerceCustomerAnalyticsRepository($DB);
    }

    public function count_new_users(int $start, int $end): int {
        global $DB;

        return $DB->count_records_select(
            'user',
            'deleted = 0
                AND timecreated >= :start
                AND timecreated < :end',
            [
                'start' => $start,
                'end' => $end,
            ]
        );
    }


    /**
     * Count distinct users starting a trial during the requested period.
     *
     * A user is counted only once during the period, even if several trial
     * subscription rows were accidentally or legitimately created.
     *
     * Trial classification comes exclusively from subscription_plan.is_trial.
     *
     * @param int $from Inclusive period start.
     * @param int $to Exclusive period end.
     * @return int
     */
    public function count_new_trials(
        int $from,
        int $to
    ): int {
        global $DB;

        $sql = "
            SELECT COUNT(DISTINCT us.userid)
            FROM {user_subscription} us
            JOIN {subscription_plan} sp
                ON sp.id = us.planid
            WHERE sp.is_trial = 1
            AND us.userid > 0
            AND us.creation_date >= :trialfrom
            AND us.creation_date < :trialto
        ";

        return (int)$DB->count_records_sql(
            $sql,
            [
                'trialfrom' => $from,
                'trialto' => $to,
            ]
        );
    }

    /**
     * Count users whose first successful subscription payment occurred
     * during the requested period.
     *
     * A user is counted once, even when several purchases were completed.
     * Renewals, upgrades and later purchases therefore do not create a
     * second new-customer event.
     *
     * Guest payments without a Moodle userid are excluded.
     *
     * @param int $from Inclusive period start.
     * @param int $to Exclusive period end.
     * @return int
     */
    public function count_new_customers(
        int $from,
        int $to
    ): int {
        global $DB;

        $native = $this->native_analytics()->snapshot($from, $to);
        if ($native->has_activity()) {
            return $native->newcustomercount;
        }

        /*
        * Keep this definition aligned with the canonical Phase 7.75A
        * payment rules:
        *
        * - successful statuses: paid / completed;
        * - a positive final amount;
        * - payment_date as the accounting date;
        * - creation_date only as a legacy fallback.
        */
        $paymentdate = "
            COALESCE(
                NULLIF(pr.payment_date, 0),
                pr.creation_date
            )
        ";

        $positiveamount = "
            (
                CASE
                    WHEN COALESCE(
                        pr.locked_final_price,
                        0
                    ) > 0
                        THEN pr.locked_final_price

                    WHEN COALESCE(
                        pr.price,
                        0
                    ) > 0
                        THEN pr.price

                    ELSE COALESCE(
                        pr.amount_minor,
                        0
                    ) / 100.0
                END
            ) > 0
        ";

        $sql = "
            SELECT COUNT(firstpayments.userid)
            FROM (
                    SELECT
                        pr.userid,
                        MIN({$paymentdate}) AS firstpaymentdate
                    FROM {subscription_payment_request} pr
                    WHERE pr.userid IS NOT NULL
                    AND pr.userid > 0
                    AND LOWER(
                        COALESCE(
                            pr.status,
                            ''
                        )
                    ) IN (
                        'paid',
                        'completed'
                    )
                    AND {$positiveamount}
                GROUP BY pr.userid
                    HAVING MIN({$paymentdate}) >= :customerfrom
                    AND MIN({$paymentdate}) < :customerto
            ) firstpayments
        ";

        return (int)$DB->count_records_sql(
            $sql,
            [
                'customerfrom' => $from,
                'customerto' => $to,
            ]
        );
    }

    /**
     * Count successful digital purchases during the period.
     */
    public function count_digital_purchases(
        int $start,
        int $end
    ): int {
        global $DB;

        $native = $this->native_analytics()->snapshot($start, $end);
        if ($native->has_activity()) {
            return (int)($native->purchasesbytype['digital'] ?? 0)
                + (int)($native->purchasesbytype['bundle'] ?? 0)
                + (int)($native->purchasesbytype['mixed'] ?? 0);
        }

        $successcondition =
            CrmBusinessSql::successful_digital_payment_condition(
                'dpr'
            );

        $dateexpression =
            CrmBusinessSql::digital_payment_date_expression(
                'dpr'
            );

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(DISTINCT dpr.id)
                  FROM {subscription_digital_payment_request} dpr
                 WHERE {$successcondition}
                   AND {$dateexpression} >= :start
                   AND {$dateexpression} < :end
            ",
            [
                'start' => $start,
                'end' => $end,
            ]
        );
    }

    /**
     * Return subscription and digital revenue grouped by currency.
     *
     * No currency conversion is performed.
     *
     * @return DashboardRevenue[]
     */
    public function get_revenue_by_currency(
        int $start,
        int $end
    ): array {
        global $DB;

        $native = $this->native_analytics()->snapshot($start, $end);
        if ($native->has_activity()) {
            $result = [];
            foreach ($native->revenuebycurrency as $currency => $totalminor) {
                $digitalminor = (int)($native->revenuebytypecurrency['digital'][$currency] ?? 0);
                $subscriptionminor = max(0, (int)$totalminor - $digitalminor);
                $result[$currency] = new DashboardRevenue(
                    $currency,
                    $subscriptionminor / 100,
                    $digitalminor / 100
                );
            }
            return $result;
        }

        $subscriptionamount =
            CrmBusinessSql::subscription_payment_amount_expression(
                'pr'
            );

        $digitalamount =
            CrmBusinessSql::digital_payment_amount_expression(
                'dpr'
            );

        $subscriptiondate =
            CrmBusinessSql::subscription_payment_date_expression(
                'pr'
            );

        $digitaldate =
            CrmBusinessSql::digital_payment_date_expression(
                'dpr'
            );

        $subscriptioncurrency =
            CrmBusinessSql::currency_expression(
                'pr.currency'
            );

        $digitalcurrency =
            CrmBusinessSql::currency_expression(
                'dpr.currency'
            );

        $subscriptioncondition =
            CrmBusinessSql::successful_subscription_payment_condition(
                'pr'
            );

        $digitalcondition =
            CrmBusinessSql::successful_digital_payment_condition(
                'dpr'
            );

        $records = $DB->get_records_sql(
            "
                SELECT
                    revenue.currency,
                    SUM(revenue.subscriptiontotal)
                        AS subscriptiontotal,
                    SUM(revenue.digitaltotal)
                        AS digitaltotal
                  FROM (
                        SELECT
                            {$subscriptioncurrency} AS currency,
                            SUM({$subscriptionamount})
                                AS subscriptiontotal,
                            0 AS digitaltotal
                          FROM {subscription_payment_request} pr
                         WHERE {$subscriptioncondition}
                           AND {$subscriptiondate} >= :subscriptionstart
                           AND {$subscriptiondate} < :subscriptionend
                           AND {$subscriptioncurrency} <> ''
                      GROUP BY {$subscriptioncurrency}

                        UNION ALL

                        SELECT
                            {$digitalcurrency} AS currency,
                            0 AS subscriptiontotal,
                            SUM({$digitalamount})
                                AS digitaltotal
                          FROM {subscription_digital_payment_request} dpr
                         WHERE {$digitalcondition}
                           AND {$digitaldate} >= :digitalstart
                           AND {$digitaldate} < :digitalend
                           AND {$digitalcurrency} <> ''
                      GROUP BY {$digitalcurrency}
                  ) revenue
              GROUP BY revenue.currency
              ORDER BY revenue.currency ASC
            ",
            [
                'subscriptionstart' => $start,
                'subscriptionend' => $end,
                'digitalstart' => $start,
                'digitalend' => $end,
            ]
        );

        $result = [];

        foreach ($records as $record) {
            $currency = Currency::sanitize(
                (string)($record->currency ?? '')
            );

            if ($currency === '') {
                continue;
            }

            $result[$currency] = new DashboardRevenue(
                $currency,
                (float)($record->subscriptiontotal ?? 0),
                (float)($record->digitaltotal ?? 0)
            );
        }

        return $result;
    }
}