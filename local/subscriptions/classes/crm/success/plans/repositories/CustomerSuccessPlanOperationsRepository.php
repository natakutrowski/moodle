<?php

namespace local_subscriptions\crm\success\plans\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;

/**
 * Aggregate queries for Dashboard, Explorer and diagnostics.
 */
final class CustomerSuccessPlanOperationsRepository {

    /**
     * @return array<string,int|float>
     */
    public function get_dashboard_metrics(): array {
        global $DB;

        [$openinsql, $openparams] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'dashboardcsopen'
            );

        $openplans =
            (int)$DB->count_records_select(
                'local_subscriptions_cs_plan',
                "status {$openinsql}",
                $openparams
            );

        $activeplans =
            (int)$DB->count_records(
                'local_subscriptions_cs_plan',
                [
                    'status' =>
                        CustomerSuccessPlanStatus::ACTIVE,
                ]
            );

        $blockedparams =
            $openparams + [
                'dashboardblocked' =>
                    CustomerSuccessPlanStepStatus::BLOCKED,
            ];

        $blockedsteps =
            (int)$DB->count_records_sql(
                "
                    SELECT COUNT(s.id)
                    FROM {local_subscriptions_cs_step} s
                    JOIN {local_subscriptions_cs_plan} p
                        ON p.id = s.planid
                    WHERE p.status {$openinsql}
                    AND s.status = :dashboardblocked
                ",
                $blockedparams
            );

        $todaystart =
            usergetmidnight(
                time()
            );

        $completedtoday =
            (int)$DB->count_records_select(
                'local_subscriptions_cs_plan',
                '
                    status = :status
                    AND completedat >= :todaystart
                ',
                [
                    'status' =>
                        CustomerSuccessPlanStatus::COMPLETED,

                    'todaystart' =>
                        $todaystart,
                ]
            );

        $criticalopen =
            (int)$DB->count_records_select(
                'local_subscriptions_cs_plan',
                "
                    status {$openinsql}
                    AND priority = :critical
                ",
                $openparams + [
                    'critical' => 'critical',
                ]
            );

        return [
            'openplans' =>
                $openplans,

            'activeplans' =>
                $activeplans,

            'blockedsteps' =>
                $blockedsteps,

            'completedtoday' =>
                $completedtoday,

            'criticalopen' =>
                $criticalopen,

            'averageprogress' =>
                $this->average_open_progress(),
        ];
    }

    public function user_has_open_plan(
        int $userid
    ): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        [$insql, $params] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'usercsopen'
            );

        $params['userid'] = $userid;

        return $DB->record_exists_select(
            'local_subscriptions_cs_plan',
            "userid = :userid AND status {$insql}",
            $params
        );
    }

    public function user_has_blocked_plan(
        int $userid
    ): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        [$openinsql, $params] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'blockedusercsopen'
            );

        $params['userid'] =
            $userid;

        $params['blockedstatus'] =
            CustomerSuccessPlanStepStatus::BLOCKED;

        $sql = "
            SELECT 1
            FROM {local_subscriptions_cs_plan} p
            JOIN {local_subscriptions_cs_step} s
                ON s.planid = p.id
            WHERE p.userid = :userid
            AND p.status {$openinsql}
            AND s.status = :blockedstatus
        ";

        return $DB->record_exists_sql(
            $sql,
            $params
        );
    }

    public function get_plan_summary(
        int $planid
    ): ?\stdClass {
        global $DB;

        if ($planid <= 0) {
            return null;
        }

        $record = $DB->get_record(
            'local_subscriptions_cs_plan',
            [
                'id' => $planid,
            ],
            '
                id,
                userid,
                objectivekey,
                title,
                status,
                priority,
                targetdate,
                timecreated
            ',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * @return \stdClass[]
     */
    public function search_open_plans(
        ?string $priority = null,
        bool $blockedonly = false,
        int $limit = 20
    ): array {
        global $DB;

        $allowedpriorities = [
            'low',
            'normal',
            'high',
            'urgent',
            'critical',
        ];

        if (
            $priority !== null &&
            !in_array(
                $priority,
                $allowedpriorities,
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan priority.'
            );
        }

        $limit = max(
            1,
            min(
                100,
                $limit
            )
        );        

        [$insql, $params] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'searchcsstatus'
            );

        $conditions = [
            "p.status {$insql}",
        ];

        if ($priority !== null) {
            $conditions[] =
                'p.priority = :searchcspriority';

            $params['searchcspriority'] =
                $priority;
        }

        if ($blockedonly) {
            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_cs_step}
                        blockedstep
                    WHERE blockedstep.planid = p.id
                    AND blockedstep.status =
                        :searchcsblocked
                )
            ";

            $params['searchcsblocked'] =
                CustomerSuccessPlanStepStatus::BLOCKED;
        }

        $sql = "
            SELECT
                p.id,
                p.userid,
                p.objectivekey,
                p.title,
                p.status,
                p.priority,
                p.targetdate,
                p.timecreated

            FROM {local_subscriptions_cs_plan} p

            WHERE " .
                implode(' AND ', $conditions) . "

        ORDER BY
                CASE p.priority
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'high' THEN 3
                    WHEN 'normal' THEN 4
                    ELSE 5
                END,
                p.timecreated ASC,
                p.id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                $params,
                0,
                $limit
            )
        );
    }

    private function average_open_progress(): float {
        global $DB;

        [$insql, $params] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'avgcsopen'
            );

        $sql = "
            SELECT p.id,
                   COUNT(s.id) AS totalsteps,
                   SUM(
                       CASE
                           WHEN s.status IN (:completed, :skipped)
                           THEN 1
                           ELSE 0
                       END
                   ) AS completedsteps
              FROM {local_subscriptions_cs_plan} p
         LEFT JOIN {local_subscriptions_cs_step} s
                ON s.planid = p.id
             WHERE p.status {$insql}
          GROUP BY p.id
        ";

        $records = $DB->get_records_sql(
            $sql,
            [
                'completed' =>
                    CustomerSuccessPlanStepStatus::COMPLETED,
                'skipped' =>
                    CustomerSuccessPlanStepStatus::SKIPPED,
            ] + $params
        );

        if ($records === []) {
            return 0.0;
        }

        $percentages = [];

        foreach ($records as $record) {
            $total = (int)$record->totalsteps;

            $percentages[] = $total > 0
                ? (
                    (int)$record->completedsteps /
                    $total
                ) * 100
                : 0;
        }

        return round(
            array_sum($percentages) /
            count($percentages),
            2
        );
    }
}