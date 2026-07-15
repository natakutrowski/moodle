<?php

namespace local_subscriptions\dashboard\inbox;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxPriority;
use local_subscriptions\crm\inbox\domain\InboxThreadStatus;
use xmldb_table;

final class DashboardInboxRepository {

    public function is_available(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(
            new xmldb_table(
                'local_subscriptions_inbox_thread'
            )
        );
    }

    public function get_counts(): object {
        global $DB;

        $sql = "
            SELECT
                COALESCE(SUM(CASE
                    WHEN t.status = :statusopen
                    THEN 1 ELSE 0
                END), 0) AS opencount,

                COALESCE(SUM(CASE
                    WHEN t.assigneduserid IS NULL
                     AND t.assignedteamid IS NULL
                    THEN 1 ELSE 0
                END), 0) AS unassignedcount,

                COALESCE(SUM(CASE
                    WHEN t.priority = :priorityurgent
                    THEN 1 ELSE 0
                END), 0) AS urgentcount,

                COALESCE(SUM(CASE
                    WHEN t.status = :statuspending
                    THEN 1 ELSE 0
                END), 0) AS pendingcount

              FROM {local_subscriptions_inbox_thread} t

             WHERE t.locallydeleted = 0
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'statusopen' =>
                    InboxThreadStatus::OPEN,

                'priorityurgent' =>
                    InboxPriority::URGENT,

                'statuspending' =>
                    InboxThreadStatus::PENDING,
            ]
        );

        return $record ?: (object)[
            'opencount' => 0,
            'unassignedcount' => 0,
            'urgentcount' => 0,
            'pendingcount' => 0,
        ];
    }

    /**
     * @return object[]
     */
    public function get_recent_threads(
        int $limit = 5
    ): array {
        global $DB;

        $sql = "
            SELECT
                t.id,
                t.subject,
                t.status,
                t.priority,
                t.unreadcount,
                t.lastmessageat,
                c.displayname AS contactname,
                c.primaryemail AS contactemail

              FROM {local_subscriptions_inbox_thread} t

         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid

             WHERE t.locallydeleted = 0

          ORDER BY t.lastmessageat DESC,
                   t.id DESC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [],
                0,
                max(
                    1,
                    min(10, $limit)
                )
            )
        );
    }
}