<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads persisted CRM score snapshots and produces period movements.
 *
 * This repository does not recalculate CRM intelligence.
 * It only reads already persisted score snapshots.
 */
final class DashboardTrendsRepository {

    public const METRIC_ENGAGEMENT_UP =
        'engagement_up';

    public const METRIC_ENGAGEMENT_DOWN =
        'engagement_down';

    public const METRIC_RISK_UP =
        'risk_up';

    public const METRIC_RISK_DOWN =
        'risk_down';

    public const METRIC_GLOBAL_UP =
        'global_up';

    public const METRIC_GLOBAL_DOWN =
        'global_down';

    public const DEFAULT_SIGNIFICANT_DELTA = 5;

    /**
     * Build one trends snapshot.
     *
     * @param int $start Inclusive period start.
     * @param int $end Exclusive period end.
     * @param int $significantdelta Minimum score movement.
     */
    public function snapshot(
        int $start,
        int $end,
        int $significantdelta =
            self::DEFAULT_SIGNIFICANT_DELTA
    ): DashboardTrendsSnapshot {
        if ($start <= 0 || $end <= $start) {
            throw new \InvalidArgumentException(
                'Invalid Dashboard trends period.'
            );
        }

        $significantdelta = max(
            1,
            $significantdelta
        );

        $currentrecords =
            $this->current_records(
                $start,
                $end
            );

        $baselinerecords =
            $this->baseline_records(
                $start
            );

        $engagementup = [];
        $engagementdown = [];
        $riskup = [];
        $riskdown = [];
        $globalup = [];
        $globaldown = [];

        $analysedusers = 0;
        $freshness = 0;

        foreach ($currentrecords as $userid => $current) {
            $userid = (int)$userid;

            $freshness = max(
                $freshness,
                (int)$current->timecreated
            );

            $baseline =
                $baselinerecords[$userid] ?? null;

            if ($baseline === null) {
                continue;
            }

            $analysedusers++;

            $engagementdelta =
                (int)$current->engagementscore
                - (int)$baseline->engagementscore;

            $riskdelta =
                (int)$current->riskscore
                - (int)$baseline->riskscore;

            $globaldelta =
                (int)$current->globalscore
                - (int)$baseline->globalscore;

            if (
                $engagementdelta >=
                $significantdelta
            ) {
                $engagementup[] = $userid;
            } else if (
                $engagementdelta <=
                -$significantdelta
            ) {
                $engagementdown[] = $userid;
            }

            /*
             * A higher risk score is a degradation.
             * A lower risk score is an improvement.
             */
            if ($riskdelta >= $significantdelta) {
                $riskup[] = $userid;
            } else if (
                $riskdelta <=
                -$significantdelta
            ) {
                $riskdown[] = $userid;
            }

            if ($globaldelta >= $significantdelta) {
                $globalup[] = $userid;
            } else if (
                $globaldelta <=
                -$significantdelta
            ) {
                $globaldown[] = $userid;
            }
        }

        $metrics = [
            self::METRIC_RISK_UP =>
                $this->metric(
                    self::METRIC_RISK_UP,
                    $riskup,
                    DashboardTrendMetric::
                        DIRECTION_DEGRADING,
                    DashboardTrendMetric::
                        SEVERITY_CRITICAL
                ),

            self::METRIC_ENGAGEMENT_DOWN =>
                $this->metric(
                    self::METRIC_ENGAGEMENT_DOWN,
                    $engagementdown,
                    DashboardTrendMetric::
                        DIRECTION_DEGRADING,
                    DashboardTrendMetric::
                        SEVERITY_WARNING
                ),

            self::METRIC_GLOBAL_DOWN =>
                $this->metric(
                    self::METRIC_GLOBAL_DOWN,
                    $globaldown,
                    DashboardTrendMetric::
                        DIRECTION_DEGRADING,
                    DashboardTrendMetric::
                        SEVERITY_WARNING
                ),

            self::METRIC_RISK_DOWN =>
                $this->metric(
                    self::METRIC_RISK_DOWN,
                    $riskdown,
                    DashboardTrendMetric::
                        DIRECTION_IMPROVING,
                    DashboardTrendMetric::
                        SEVERITY_POSITIVE
                ),

            self::METRIC_ENGAGEMENT_UP =>
                $this->metric(
                    self::METRIC_ENGAGEMENT_UP,
                    $engagementup,
                    DashboardTrendMetric::
                        DIRECTION_IMPROVING,
                    DashboardTrendMetric::
                        SEVERITY_POSITIVE
                ),

            self::METRIC_GLOBAL_UP =>
                $this->metric(
                    self::METRIC_GLOBAL_UP,
                    $globalup,
                    DashboardTrendMetric::
                        DIRECTION_IMPROVING,
                    DashboardTrendMetric::
                        SEVERITY_POSITIVE
                ),
        ];

        return new DashboardTrendsSnapshot(
            $start,
            $end,
            $metrics,
            $analysedusers,
            count($currentrecords),
            $freshness
        );
    }

    /**
     * Latest snapshot for every user during the period.
     *
     * @return \stdClass[] Indexed by userid.
     */
    private function current_records(
        int $start,
        int $end
    ): array {
        global $DB;

        $records = $DB->get_records_sql(
            "
            SELECT score.*
              FROM {local_subscriptions_crm_score} score
             WHERE score.id = (
                    SELECT MAX(latestscore.id)
                      FROM {local_subscriptions_crm_score}
                        latestscore
                     WHERE latestscore.userid =
                            score.userid
                       AND latestscore.timecreated
                            >= :currentstart
                       AND latestscore.timecreated
                            < :currentend
             )
            ",
            [
                'currentstart' => $start,
                'currentend' => $end,
            ]
        );

        return $this->index_by_userid(
            $records
        );
    }

    /**
     * Latest snapshot for every user before the period.
     *
     * @return \stdClass[] Indexed by userid.
     */
    private function baseline_records(
        int $start
    ): array {
        global $DB;

        $records = $DB->get_records_sql(
            "
            SELECT score.*
              FROM {local_subscriptions_crm_score} score
             WHERE score.id = (
                    SELECT MAX(baselinescore.id)
                      FROM {local_subscriptions_crm_score}
                        baselinescore
                     WHERE baselinescore.userid =
                            score.userid
                       AND baselinescore.timecreated
                            < :baselinestart
             )
            ",
            [
                'baselinestart' => $start,
            ]
        );

        return $this->index_by_userid(
            $records
        );
    }

    /**
     * Index records by user ID.
     *
     * @param \stdClass[] $records
     * @return \stdClass[]
     */
    private function index_by_userid(
        array $records
    ): array {
        $indexed = [];

        foreach ($records as $record) {
            $userid = (int)($record->userid ?? 0);

            if ($userid <= 0) {
                continue;
            }

            $indexed[$userid] = $record;
        }

        return $indexed;
    }

    /**
     * Create one normalized trend metric.
     *
     * @param string $key
     * @param int[] $userids
     * @param string $direction
     * @param string $severity
     */
    private function metric(
        string $key,
        array $userids,
        string $direction,
        string $severity
    ): DashboardTrendMetric {
        $userids = array_values(
            array_unique(
                array_map(
                    'intval',
                    $userids
                )
            )
        );

        return new DashboardTrendMetric(
            $key,
            count($userids),
            $direction,
            $severity,
            $userids
        );
    }
}