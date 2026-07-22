<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileRepository {

    public function get_user(int $userid): ?\stdClass {
        global $DB;

        $user = $DB->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            '*',
            IGNORE_MISSING
        );

        return $user ?: null;
    }

    /**
     * Resolves an active, deleted or missing Moodle user.
     */
    public function resolve_user(
        int $userid
    ): UserProfileLookupResult {
        global $DB;

        $user = $DB->get_record(
            'user',
            [
                'id' => $userid,
            ],
            '*',
            IGNORE_MISSING
        );

        if (!$user) {
            return UserProfileLookupResult::missing(
                $userid
            );
        }

        if (!empty($user->deleted)) {
            return UserProfileLookupResult::deleted(
                $user
            );
        }

        return UserProfileLookupResult::active(
            $user
        );
    }

    public function get_subscriptions(int $userid): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT us.*, sp.name AS planname, sp.duration_key
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
             WHERE us.userid = :userid
          ORDER BY us.start_date DESC, us.id DESC
        ", ['userid' => $userid]));
    }

    public function get_digital_payments(int $userid, string $email, int $limit = 20): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            return [];
        }

        return array_values($DB->get_records_sql("
            SELECT dpr.*, dp.name AS productname
              FROM {subscription_digital_payment_request} dpr
         LEFT JOIN {subscription_digital_product} dp ON dp.id = dpr.productid
             WHERE dpr.userid = :userid OR dpr.email = :email
          ORDER BY dpr.creation_date DESC, dpr.id DESC
        ", [
            'userid' => $userid,
            'email' => $email,
        ], 0, $limit));
    }

    public function get_accessible_courses(int $userid): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT DISTINCT
                   c.id,
                   c.fullname,
                   c.shortname,
                   ue.timestart,
                   ue.timeend,
                   ue.status AS enrolstatus,
                   e.enrol
              FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
             WHERE ue.userid = :userid
               AND c.id <> :siteid
               AND ue.status = 0
               AND e.status = 0
               AND (ue.timeend = 0 OR ue.timeend > :now)
          ORDER BY c.fullname ASC
        ", [
            'userid' => $userid,
            'siteid' => SITEID,
            'now' => time(),
        ]));
    }

    public function count_accessible_courses(int $userid): int {
        global $DB;

        return (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT e.courseid)
              FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
             WHERE ue.userid = :userid
               AND ue.status = 0
               AND e.status = 0
        ", ['userid' => $userid]);
    }

    /**
     * Counts all historical course enrolments for a user.
     *
     * Unlike count_accessible_courses(), this method also includes inactive
     * and expired enrolments because the historical profile is read-only.
     */
    public function count_historical_courses(
        int $userid
    ): int {
        global $DB;

        return (int)$DB->count_records_sql(
            "
            SELECT COUNT(DISTINCT e.courseid)
            FROM {user_enrolments} ue
            JOIN {enrol} e
                ON e.id = ue.enrolid
            WHERE ue.userid = :userid
            AND e.courseid <> :siteid
            ",
            [
                'userid' => $userid,
                'siteid' => SITEID,
            ]
        );
    }

    /**
     * Loads historical digital payments strictly by Moodle user ID.
     *
     * The deleted Moodle user's current email must not be used because Moodle
     * may have anonymised it during account deletion.
     */
    public function get_historical_digital_payments(
        int $userid,
        int $limit = 50
    ): array {
        global $DB;

        if (
            !$DB->get_manager()->table_exists(
                'subscription_digital_payment_request'
            )
        ) {
            return [];
        }

        return array_values(
            $DB->get_records_sql(
                "
                SELECT dpr.*,
                    dp.name AS productname
                FROM {subscription_digital_payment_request} dpr
            LEFT JOIN {subscription_digital_product} dp
                    ON dp.id = dpr.productid
                WHERE dpr.userid = :userid
            ORDER BY dpr.creation_date DESC,
                    dpr.id DESC
                ",
                [
                    'userid' => $userid,
                ],
                0,
                $limit
            )
        );
    }

    /**
     * Returns historical revenue totals indexed by currency.
     *
     * @return array<string, float>
     */
    public function get_revenue_by_currency(
        int $userid
    ): array {
        global $DB;

        $totals = [];

        $subscriptionrecords = $DB->get_records_sql(
            "
            SELECT currency,
                COALESCE(SUM(pricepaid), 0) AS total
            FROM {user_subscription}
            WHERE userid = :userid
            AND currency IS NOT NULL
            AND currency <> ''
            AND status IN (
                'active',
                'expired',
                'cancelled',
                'replaced'
            )
        GROUP BY currency
            ",
            [
                'userid' => $userid,
            ]
        );

        foreach ($subscriptionrecords as $record) {
            $currency = strtoupper(
                trim((string)$record->currency)
            );

            if ($currency === '') {
                continue;
            }

            $totals[$currency] =
                ($totals[$currency] ?? 0.0)
                + (float)$record->total;
        }

        if (
            $DB->get_manager()->table_exists(
                'subscription_digital_payment_request'
            )
        ) {
            $digitalrecords = $DB->get_records_sql(
                "
                SELECT currency,
                    COALESCE(SUM(price), 0) AS total
                FROM {subscription_digital_payment_request}
                WHERE userid = :userid
                AND currency IS NOT NULL
                AND currency <> ''
                AND status IN (
                    'paid',
                    'completed',
                    'PAID',
                    'COMPLETED'
                )
            GROUP BY currency
                ",
                [
                    'userid' => $userid,
                ]
            );

            foreach ($digitalrecords as $record) {
                $currency = strtoupper(
                    trim((string)$record->currency)
                );

                if ($currency === '') {
                    continue;
                }

                $totals[$currency] =
                    ($totals[$currency] ?? 0.0)
                    + (float)$record->total;
            }
        }

        ksort($totals);

        return $totals;
    }

    public function has_subscription_status(int $userid, string $status): bool {
        global $DB;

        return $DB->record_exists('user_subscription', [
            'userid' => $userid,
            'status' => $status,
        ]);
    }

    public function has_past_subscription(int $userid): bool {
        global $DB;

        return $DB->record_exists_select(
            'user_subscription',
            'userid = :userid AND status IN (:expired, :cancelled, :replaced)',
            [
                'userid' => $userid,
                'expired' => 'expired',
                'cancelled' => 'cancelled',
                'replaced' => 'replaced',
            ]
        );
    }

    public function sum_spent_by_currency(int $userid, string $currency): float {
        global $DB;

        $subsum = (float)$DB->get_field_sql("
            SELECT COALESCE(SUM(pricepaid), 0)
              FROM {user_subscription}
             WHERE userid = :userid
               AND status IN ('active', 'expired', 'cancelled', 'replaced')
               AND currency = :currency
        ", [
            'userid' => $userid,
            'currency' => $currency,
        ]);

        $digitalsum = 0.0;

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $digitalsum = (float)$DB->get_field_sql("
                SELECT COALESCE(SUM(price), 0)
                  FROM {subscription_digital_payment_request}
                 WHERE userid = :userid
                   AND status IN ('paid', 'completed', 'PAID', 'COMPLETED')
                   AND currency = :currency
            ", [
                'userid' => $userid,
                'currency' => $currency,
            ]);
        }

        return $subsum + $digitalsum;
    }

    public function last_activity(int $userid): int {
        global $DB;

        $lasts = [];

        $lasts[] = (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(COALESCE(last_update, creation_date)), 0)
              FROM {user_subscription}
             WHERE userid = :userid
        ", ['userid' => $userid]);

        $lasts[] = (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(timecreated), 0)
              FROM {local_subscriptions_admin_log}
             WHERE targetuserid = :userid
        ", ['userid' => $userid]);

        if ($DB->get_manager()->table_exists('subscription_payment_request')) {
            $field = $DB->get_manager()->field_exists('subscription_payment_request', 'last_update')
                ? 'last_update'
                : 'creation_date';

            $lasts[] = (int)$DB->get_field_sql("
                SELECT COALESCE(MAX($field), 0)
                  FROM {subscription_payment_request}
                 WHERE userid = :userid
            ", ['userid' => $userid]);
        }

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $field = $DB->get_manager()->field_exists('subscription_digital_payment_request', 'last_update')
                ? 'last_update'
                : 'creation_date';

            $lasts[] = (int)$DB->get_field_sql("
                SELECT COALESCE(MAX($field), 0)
                  FROM {subscription_digital_payment_request}
                 WHERE userid = :userid
            ", ['userid' => $userid]);
        }

        return max($lasts);
    }

    public function get_admin_logs(int $userid, int $limit): array {
        global $DB;

        return array_values($DB->get_records(
            'local_subscriptions_admin_log',
            ['targetuserid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    /**
     * Loads timeline actors indexed by user ID.
     *
     * @param int[] $actorids
     * @return array<int, \stdClass>
     */
    public function get_timeline_actors(
        array $actorids
    ): array {
        global $DB;

        $actorids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $actorids
                    ),
                    static fn(int $actorid): bool =>
                        $actorid > 0
                )
            )
        );

        if ($actorids === []) {
            return [];
        }

        [$insql, $params] =
            $DB->get_in_or_equal(
                $actorids,
                SQL_PARAMS_NAMED,
                'actor'
            );

        $records = $DB->get_records_select(
            'user',
            'id ' . $insql,
            $params,
            '',
            'id, firstname, lastname, email'
        );

        $actors = [];

        foreach ($records as $record) {
            $actors[(int)$record->id] = $record;
        }

        return $actors;
    }

    public function get_user_notes(int $userid, int $limit): array {
        global $DB;

        return array_values($DB->get_records(
            'local_subscriptions_user_note',
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function get_subscription_payments_for_timeline(int $userid, string $email, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('subscription_payment_request')) {
            return [];
        }

        return array_values($DB->get_records_sql("
            SELECT pr.*, sp.name AS planname
            FROM {subscription_payment_request} pr
        LEFT JOIN {subscription_plan} sp ON sp.id = pr.planid
            WHERE pr.userid = :userid OR pr.email = :email
        ORDER BY COALESCE(pr.payment_date, pr.creation_date) DESC, pr.id DESC
        ", [
            'userid' => $userid,
            'email' => $email,
        ], 0, $limit));
    }

    public function get_subscriptions_for_timeline(int $userid, int $limit): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT us.*, sp.name AS planname
            FROM {user_subscription} us
        LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
            WHERE us.userid = :userid
        ORDER BY us.creation_date DESC, us.id DESC
        ", ['userid' => $userid], 0, $limit));
    }

    public function get_digital_purchases_for_timeline(int $userid, string $email, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            return [];
        }

        return array_values($DB->get_records_sql("
            SELECT dpr.*, dp.name AS productname
            FROM {subscription_digital_payment_request} dpr
        LEFT JOIN {subscription_digital_product} dp ON dp.id = dpr.productid
            WHERE dpr.userid = :userid OR dpr.email = :email
        ORDER BY dpr.creation_date DESC, dpr.id DESC
        ", [
            'userid' => $userid,
            'email' => $email,
        ], 0, $limit));
    }

    public function get_digital_purchase_logs(array $purchaseids, int $limit): array {
        global $DB;

        if (!$purchaseids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED);

        return array_values($DB->get_records_select(
            'local_subscriptions_admin_log',
            "objecttype = :objecttype AND objectid $insql",
            ['objecttype' => 'digital_purchase'] + $params,
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function get_notes_for_profile(int $userid, int $limit = 20): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_subscriptions_user_note')) {
            return [];
        }

        return array_values($DB->get_records_sql("
            SELECT n.*
            FROM {local_subscriptions_user_note} n
            WHERE n.userid = :userid
        ORDER BY n.timecreated DESC, n.id DESC
        ", ['userid' => $userid], 0, $limit));
    }

    public function add_note(int $userid, int $authorid, string $note, string $type = 'general'): int {
        global $DB;

        $record = (object)[
            'userid' => $userid,
            'authorid' => $authorid,
            'note' => $note,
            'type' => $type,
            'timecreated' => time(),
        ];

        return (int)$DB->insert_record('local_subscriptions_user_note', $record);
    }

    public function get_tags_for_profile(int $userid): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_subscriptions_user_tag')) {
            return [];
        }

        return array_values($DB->get_records(
            'local_subscriptions_user_tag',
            ['userid' => $userid],
            'timecreated DESC, id DESC'
        ));
    }

    public function add_tag(int $userid, string $tag, int $createdby): void {
        global $DB;

        if ($DB->record_exists('local_subscriptions_user_tag', [
            'userid' => $userid,
            'tag' => $tag,
        ])) {
            return;
        }

        $DB->insert_record('local_subscriptions_user_tag', (object)[
            'userid' => $userid,
            'tag' => $tag,
            'createdby' => $createdby,
            'timecreated' => time(),
        ]);
    }

    public function remove_tag(int $userid, string $tag): void {
        global $DB;

        $DB->delete_records('local_subscriptions_user_tag', [
            'userid' => $userid,
            'tag' => $tag,
        ]);
    }

    public function get_automation_history_for_timeline(int $userid, int $limit): array {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_subscriptions_automation_history')) {
            return [];
        }

        return array_values($DB->get_records(
            'local_subscriptions_automation_history',
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function get_user_tags(int $userid, int $limit = 50): array {
        global $DB;

        return array_values($DB->get_records(
            'local_subscriptions_user_tag',
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function get_inbox_messages_for_timeline(
        int $userid,
        int $limit
    ): array {
        global $DB;

        $manager = $DB->get_manager();

        $contacttable = new \xmldb_table(
            'local_subscriptions_inbox_contact'
        );

        $threadtable = new \xmldb_table(
            'local_subscriptions_inbox_thread'
        );

        $messagetable = new \xmldb_table(
            'local_subscriptions_inbox_message'
        );

        if (
            !$manager->table_exists($contacttable) ||
            !$manager->table_exists($threadtable) ||
            !$manager->table_exists($messagetable)
        ) {
            return [];
        }

        $sql = "
            SELECT
                m.id,
                m.threadid,
                m.direction,
                m.status,
                m.subject,
                m.bodytext,
                m.bodyhtml,
                m.receivedat,
                m.sentat,
                m.timecreated,
                m.createdby,
                t.subject AS threadsubject,
                c.primaryemail AS contactemail,
                c.displayname AS contactname
            FROM {local_subscriptions_inbox_message} m
            JOIN {local_subscriptions_inbox_thread} t
                ON t.id = m.threadid
            JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid
            WHERE c.matcheduserid = :userid
            AND t.locallydeleted = 0
            AND m.status <> :draftstatus
        ORDER BY
                COALESCE(
                    m.receivedat,
                    m.sentat,
                    m.timecreated
                ) DESC,
                m.id DESC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'userid' => $userid,
                    'draftstatus' => 'draft',
                ],
                0,
                max(1, $limit)
            )
        );
    }

}