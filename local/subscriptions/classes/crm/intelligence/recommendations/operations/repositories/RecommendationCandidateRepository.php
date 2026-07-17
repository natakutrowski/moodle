<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;

/**
 * Loads CRM users eligible for scheduled recommendation calculation.
 */
final class RecommendationCandidateRepository {

    /**
     * @return \stdClass[]
     */
    public function get_after(
        int $afteruserid,
        int $limit
    ): array {
        global $DB;

        $limit = RecommendationBatchLimits::
            normalize_limit($limit);

        $recent =
            time() -
            (
                RecommendationBatchLimits::
                    RECENT_ACTIVITY_DAYS *
                DAYSECS
            );

        return array_values($DB->get_records_sql(
            "SELECT u.*
               FROM {user} u
              WHERE u.deleted = 0
                AND u.confirmed = 1
                AND u.id > :afteruserid
                AND (
                    u.lastaccess >= :recent
                    OR EXISTS (
                        SELECT 1
                          FROM {user_subscription} us
                         WHERE us.userid = u.id
                    )
                    OR EXISTS (
                        SELECT 1
                          FROM {local_subscriptions_inbox_contact} contact
                         WHERE contact.matcheduserid = u.id
                    )
                    OR EXISTS (
                        SELECT 1
                          FROM {local_subscriptions_work_item} item
                         WHERE item.targetuserid = u.id
                    )
                )
           ORDER BY u.id ASC",
            [
                'afteruserid' => $afteruserid,
                'recent' => $recent,
            ],
            0,
            $limit
        ));
    }

    public function has_candidates_after(
        int $afteruserid
    ): bool {
        return $this->get_after(
            $afteruserid,
            1
        ) !== [];
    }
}