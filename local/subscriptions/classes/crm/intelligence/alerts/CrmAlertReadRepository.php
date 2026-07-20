<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

/**
 * Read repository for persisted CRM Intelligence alert inputs.
 *
 * This repository reads the latest score snapshot for each user.
 * It must never trigger Intelligence calculations.
 */
final class CrmAlertReadRepository {

    private const SCORE_TABLE =
        'local_subscriptions_crm_score';

    /**
     * Returns the latest persisted snapshots eligible for alerts.
     *
     * One row maximum is returned per active Moodle user.
     *
     * @param int $limit Maximum number of users to inspect.
     * @return \stdClass[]
     */
    public function get_latest_snapshots(
        int $limit
    ): array {
        global $DB;

        $limit = max(1, min(200, $limit));

        $records = $DB->get_records_sql(
            "
            SELECT
                score.id AS snapshotid,
                score.userid,
                score.commercialscore,
                score.engagementscore,
                score.riskscore,
                score.globalscore,
                score.segmentsjson,
                score.opportunitiesjson,
                score.timecreated AS snapshottime,

                u.firstname,
                u.lastname,
                u.firstnamephonetic,
                u.lastnamephonetic,
                u.middlename,
                u.alternatename,
                u.email

            FROM {" . self::SCORE_TABLE . "} score

            JOIN {user} u
                ON u.id = score.userid
            AND u.deleted = 0
            AND u.suspended = 0

            WHERE NOT EXISTS (
                    SELECT 1
                    FROM {" . self::SCORE_TABLE . "} newer
                    WHERE newer.userid = score.userid
                    AND (
                            newer.timecreated >
                                score.timecreated
                            OR (
                                newer.timecreated =
                                    score.timecreated
                                AND newer.id >
                                    score.id
                            )
                    )
            )

        ORDER BY score.riskscore DESC,
                score.commercialscore DESC,
                score.timecreated DESC,
                score.id DESC
            ",
            [],
            0,
            $limit
        );

        return array_values($records);
    }
}