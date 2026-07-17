<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;

/**
 * Operational counters for Recommendation Engine diagnostics.
 */
final class RecommendationOperationsRepository {

    private const RECOMMENDATION_TABLE =
        'local_subscriptions_recommendation';

    private const RUN_TABLE =
        'local_subscriptions_recommendation_run';

    public function counters(): \stdClass {
        global $DB;

        $now = time();

        return $DB->get_record_sql(
            "SELECT
                    COUNT(1) AS totalcount,

                    SUM(
                        CASE
                            WHEN r.status IN (:proposed, :accepted)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowactive
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS activecount,

                    SUM(
                        CASE
                            WHEN r.status IN (:proposedexpired, :acceptedexpired)
                             AND r.validuntil IS NOT NULL
                             AND r.validuntil <= :nowexpired
                            THEN 1 ELSE 0
                        END
                    ) AS dueexpirationcount,

                    SUM(
                        CASE
                            WHEN r.prioritylevel = 'critical'
                             AND r.status IN (:proposedcritical, :acceptedcritical)
                             AND (
                                 r.validuntil IS NULL
                                 OR r.validuntil > :nowcritical
                             )
                            THEN 1 ELSE 0
                        END
                    ) AS criticalcount,

                    MAX(r.lastdetectedat)
                        AS lastdetectedat
               FROM {" . self::RECOMMENDATION_TABLE . "} r",
            [
                'proposed' =>
                    RecommendationStatus::PROPOSED,
                'accepted' =>
                    RecommendationStatus::ACCEPTED,
                'nowactive' => $now,

                'proposedexpired' =>
                    RecommendationStatus::PROPOSED,
                'acceptedexpired' =>
                    RecommendationStatus::ACCEPTED,
                'nowexpired' => $now,

                'proposedcritical' =>
                    RecommendationStatus::PROPOSED,
                'acceptedcritical' =>
                    RecommendationStatus::ACCEPTED,
                'nowcritical' => $now,
            ]
        );
    }

    public function failed_runs_since(
        int $since
    ): int {
        global $DB;

        return $DB->count_records_select(
            self::RUN_TABLE,
            'startedat >= :since
             AND status IN (:failed, :partial)',
            [
                'since' => $since,
                'failed' => 'failed',
                'partial' => 'partial',
            ]
        );
    }
}