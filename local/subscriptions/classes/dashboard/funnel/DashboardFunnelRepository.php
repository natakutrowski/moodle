<?php

namespace local_subscriptions\dashboard\funnel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\business\CrmBusinessSql;

/**
 * Funnel cohort queries.
 */
final class DashboardFunnelRepository {

    /**
     * Build one Funnel snapshot.
     *
     * @param int $start Inclusive period start.
     * @param int $end Exclusive period end.
     * @param int $conversionwindowdays Trial conversion window.
     */
    public function snapshot(
        int $start,
        int $end,
        int $conversionwindowdays = 30
    ): DashboardFunnelSnapshot {
        $conversionwindowdays = max(
            1,
            $conversionwindowdays
        );

        $newusers = $this->count_new_users(
            $start,
            $end
        );

        $trialmetrics = $this->get_trial_metrics(
            $start,
            $end,
            $conversionwindowdays
        );

        return new DashboardFunnelSnapshot(
            $start,
            $end,
            $newusers,
            $trialmetrics['trialusers'],
            $trialmetrics['convertedtrialusers'],
            $trialmetrics['maturetrialusers'],
            $trialmetrics['convertedmaturetrialusers'],
            $trialmetrics['pendingtrialusers'],
            $this->count_new_customers(
                $start,
                $end
            ),
            $this->count_digital_buyers(
                $start,
                $end
            )
        );
    }

    private function count_new_users(
        int $start,
        int $end
    ): int {
        global $DB;

        return (int)$DB->count_records_select(
            'user',
            'deleted = 0
                AND id > 2
                AND timecreated >= :start
                AND timecreated < :end',
            [
                'start' => $start,
                'end' => $end,
            ]
        );
    }

    /**
     * Return metrics for users whose first trial starts in the period.
     *
     * @return array{
     *     trialusers: int,
     *     convertedtrialusers: int,
     *     maturetrialusers: int,
     *     convertedmaturetrialusers: int,
     *     pendingtrialusers: int
     * }
     */
    private function get_trial_metrics(
        int $start,
        int $end,
        int $conversionwindowdays
    ): array {
        global $DB;

        $paymentdate =
            CrmBusinessSql::subscription_payment_date_expression(
                'pr'
            );

        $paymentcondition =
            CrmBusinessSql::successful_subscription_payment_condition(
                'pr'
            );

        $windowseconds =
            $conversionwindowdays * DAYSECS;

        $now = time();
        $maturitylimit = $now - $windowseconds;

        $sql = "
            SELECT
                COUNT(*) AS trialusers,

                SUM(
                    CASE
                        WHEN cohort.firstpaymentdate IS NOT NULL
                            THEN 1
                        ELSE 0
                    END
                ) AS convertedtrialusers,

                SUM(
                    CASE
                        WHEN cohort.firsttrialdate <= :maturitylimit
                            THEN 1
                        ELSE 0
                    END
                ) AS maturetrialusers,

                SUM(
                    CASE
                        WHEN cohort.firsttrialdate <= :maturitylimit2
                         AND cohort.firstpaymentdate IS NOT NULL
                            THEN 1
                        ELSE 0
                    END
                ) AS convertedmaturetrialusers

              FROM (
                    SELECT
                        trials.userid,
                        trials.firsttrialdate,
                        MIN({$paymentdate}) AS firstpaymentdate

                      FROM (
                            SELECT
                                us.userid,
                                MIN(us.creation_date)
                                    AS firsttrialdate
                              FROM {user_subscription} us
                              JOIN {subscription_plan} sp
                                ON sp.id = us.planid
                             WHERE sp.is_trial = 1
                               AND us.userid > 0
                          GROUP BY us.userid
                            HAVING MIN(us.creation_date) >= :trialstart
                               AND MIN(us.creation_date) < :trialend
                      ) trials

                 LEFT JOIN {subscription_payment_request} pr
                        ON pr.userid = trials.userid
                       AND {$paymentcondition}
                       AND {$paymentdate} >= trials.firsttrialdate
                       AND {$paymentdate}
                            <= trials.firsttrialdate + :windowseconds

                  GROUP BY
                        trials.userid,
                        trials.firsttrialdate
              ) cohort
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'trialstart' => $start,
                'trialend' => $end,
                'windowseconds' => $windowseconds,
                'maturitylimit' => $maturitylimit,
                'maturitylimit2' => $maturitylimit,
            ]
        );

        $trialusers =
            (int)($record->trialusers ?? 0);

        $convertedtrialusers =
            (int)($record->convertedtrialusers ?? 0);

        $maturetrialusers =
            (int)($record->maturetrialusers ?? 0);

        $convertedmaturetrialusers =
            (int)($record->convertedmaturetrialusers ?? 0);

        return [
            'trialusers' => $trialusers,
            'convertedtrialusers' =>
                $convertedtrialusers,
            'maturetrialusers' =>
                $maturetrialusers,
            'convertedmaturetrialusers' =>
                $convertedmaturetrialusers,
            'pendingtrialusers' => max(
                0,
                $trialusers - $maturetrialusers
            ),
        ];
    }

    /**
     * Count users whose first successful paid subscription purchase
     * occurred during the period.
     */
    private function count_new_customers(
        int $start,
        int $end
    ): int {
        global $DB;

        $paymentdate =
            CrmBusinessSql::subscription_payment_date_expression(
                'pr'
            );

        $paymentcondition =
            CrmBusinessSql::successful_subscription_payment_condition(
                'pr'
            );

        $sql = "
            SELECT COUNT(firstpayments.userid)
              FROM (
                    SELECT
                        pr.userid,
                        MIN({$paymentdate}) AS firstpaymentdate
                      FROM {subscription_payment_request} pr
                     WHERE pr.userid > 0
                       AND {$paymentcondition}
                  GROUP BY pr.userid
                    HAVING MIN({$paymentdate}) >= :customerstart
                       AND MIN({$paymentdate}) < :customerend
              ) firstpayments
        ";

        return (int)$DB->count_records_sql(
            $sql,
            [
                'customerstart' => $start,
                'customerend' => $end,
            ]
        );
    }

    /**
     * Count distinct users with successful digital purchases.
     */
    private function count_digital_buyers(
        int $start,
        int $end
    ): int {
        global $DB;

        $condition =
            CrmBusinessSql::successful_digital_payment_condition(
                'dpr'
            );

        $paymentdate =
            CrmBusinessSql::digital_payment_date_expression(
                'dpr'
            );

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(DISTINCT dpr.userid)
                  FROM {subscription_digital_payment_request} dpr
                 WHERE dpr.userid > 0
                   AND {$condition}
                   AND {$paymentdate} >= :digitalstart
                   AND {$paymentdate} < :digitalend
            ",
            [
                'digitalstart' => $start,
                'digitalend' => $end,
            ]
        );
    }
}