<?php

namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

final class SubscriptionReminderRepository {

    public function find_candidates(int $now): array {
        global $DB;

        $sql = "SELECT s.*,
                       p.accessscopeid,
                       p.duration_key,
                       p.expiry_reminder_days,
                       p.expiry_reminder_enabled
                  FROM {user_subscription} s
                  JOIN {subscription_plan} p
                    ON p.id = s.planid
                 WHERE s.status = :active
                   AND (p.is_recurring = 0 OR p.is_recurring IS NULL)
                   AND (p.is_trial = 0 OR p.is_trial IS NULL)
                   AND s.end_date IS NOT NULL
                   AND s.end_date > :now";

        return $DB->get_records_sql(
            $sql,
            [
                'active' => Status::ACTIVE,
                'now' => $now,
            ],
        );
    }

    public function has_queued_in_scope(\stdClass $subscription): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {user_subscription} qs
                  JOIN {subscription_plan} qp
                    ON qp.id = qs.planid
                 WHERE qs.userid = :userid
                   AND qs.status = :queued
                   AND qp.accessscopeid = :scopeid
                   AND qs.start_date >= :minimumstart";

        return $DB->record_exists_sql(
            $sql,
            [
                'userid' => (int) $subscription->userid,
                'queued' => Status::QUEUED,
                'scopeid' => (int) $subscription->accessscopeid,
                'minimumstart' => (int) $subscription->end_date - HOURSECS,
            ],
        );
    }

    public function reminder_exists(
        int $subscriptionid,
        string $newkey,
        string $oldkey,
    ): bool {
        global $DB;

        return $DB->record_exists_select(
            'subscription_reminder_log',
            'subscriptionid = :subscriptionid AND remind_key IN (:newkey, :oldkey)',
            [
                'subscriptionid' => $subscriptionid,
                'newkey' => $newkey,
                'oldkey' => $oldkey,
            ],
        );
    }

    public function user(int $userid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            '*',
            IGNORE_MISSING,
        ) ?: null;
    }

    public function plan(int $planid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'subscription_plan',
            ['id' => $planid],
            '*',
            IGNORE_MISSING,
        ) ?: null;
    }

    public function record_sent(\stdClass $subscription, string $key, int $now): void {
        global $DB;

        $DB->insert_record(
            'subscription_reminder_log',
            (object) [
                'subscriptionid' => $subscription->id,
                'userid' => $subscription->userid,
                'remind_key' => $key,
                'sent_at' => $now,
            ],
        );
    }
}
