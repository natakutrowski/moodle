<?php

namespace local_subscriptions\crm\intelligence\priority;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\RecommendationTarget;

/**
 * Read repository for persisted CRM daily priorities.
 *
 * Daily priorities are derived from active persistent recommendations.
 * This repository never triggers Intelligence or Customer Success
 * calculations.
 */
final class DailyPriorityRepository {

    private const RECOMMENDATION_TABLE =
        'local_subscriptions_recommendation';

    /**
     * Returns active user recommendations ordered as daily priorities.
     *
     * The user information required by fullname() is loaded in the same
     * query to avoid an additional query for every priority.
     *
     * @param int $limit Maximum number of priorities.
     * @return \stdClass[]
     */
    public function get_active_user_priorities(
        int $limit
    ): array {
        global $DB;

        $limit = max(1, min(100, $limit));

        [$statussql, $params] = $DB->get_in_or_equal(
            RecommendationStatus::active(),
            SQL_PARAMS_NAMED,
            'prioritystatus'
        );

        $params['targettype'] =
            RecommendationTarget::USER;

        $params['now'] = time();

        $records = $DB->get_records_sql(
            "SELECT
                    r.id AS recommendationid,
                    r.recommendationkey,
                    r.priority,
                    r.prioritylevel,
                    r.presentationtype,
                    r.lastdetectedat,
                    r.validuntil,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    u.firstnamephonetic,
                    u.lastnamephonetic,
                    u.middlename,
                    u.alternatename
               FROM {" . self::RECOMMENDATION_TABLE . "} r
               JOIN {user} u
                 ON u.id = r.targetid
              WHERE r.targettype = :targettype
                AND r.targetid IS NOT NULL
                AND r.status {$statussql}
                AND u.deleted = 0
                AND (
                    r.validuntil IS NULL
                    OR r.validuntil > :now
                )
           ORDER BY r.priority DESC,
                    r.lastdetectedat DESC,
                    r.id DESC",
            $params,
            0,
            $limit
        );

        return array_values($records);
    }
}