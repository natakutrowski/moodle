<?php

namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

final class SubscriptionAccessRepairRepository {

    public function find_active_ids(int $now, int $limit): array {
        global $DB;

        $sql = "SELECT s.id
                  FROM {user_subscription} s
                  JOIN {user} u
                    ON u.id = s.userid
                 WHERE s.status = :active
                   AND (s.end_date = 0 OR s.end_date > :now)
                   AND u.deleted = 0
                   AND u.suspended = 0
              ORDER BY s.id ASC";

        $records = $DB->get_records_sql(
            $sql,
            [
                'active' => Status::ACTIVE,
                'now' => $now,
            ],
            0,
            $limit,
        );

        return array_map('intval', array_keys($records));
    }

    public function find(int $subscriptionid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'user_subscription',
            [
                'id' => $subscriptionid,
                'status' => Status::ACTIVE,
            ],
            'id, userid, planid, start_date, end_date',
            IGNORE_MISSING,
        ) ?: null;
    }
}
