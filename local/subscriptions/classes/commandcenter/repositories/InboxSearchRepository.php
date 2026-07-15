<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxSearchRepository {

    /**
     * @return object[]
     */
    public function search_threads(
        string $text,
        int $limit = 10
    ): array {
        global $DB;

        $text = trim($text);

        if (
            \core_text::strlen($text) < 2
        ) {
            return [];
        }

        $limit = max(
            1,
            min(20, $limit)
        );

        $query = '%' .
            $DB->sql_like_escape(
                \core_text::strtolower($text)
            ) .
            '%';

        $conditions = [
            $DB->sql_like(
                'LOWER(' .
                $DB->sql_cast_to_char(
                    't.subject'
                ) .
                ')',
                ':threadsubject',
                false
            ),

            $DB->sql_like(
                'LOWER(c.primaryemail)',
                ':threademail',
                false
            ),

            $DB->sql_like(
                'LOWER(c.displayname)',
                ':threadname',
                false
            ),
        ];

        $sql = "
            SELECT
                t.id,
                t.subject,
                t.status,
                t.priority,
                t.unreadcount,
                t.lastmessageat,
                t.assigneduserid,
                t.assignedteamid,

                c.id AS contactid,
                c.displayname AS contactname,
                c.primaryemail AS contactemail

              FROM {local_subscriptions_inbox_thread} t

         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid

             WHERE t.locallydeleted = 0
               AND (
                    " .
                    implode(
                        ' OR ',
                        $conditions
                    ) .
                    "
               )

          ORDER BY
                CASE t.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END ASC,

                t.lastmessageat DESC,
                t.id DESC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'threadsubject' => $query,
                    'threademail' => $query,
                    'threadname' => $query,
                ],
                0,
                $limit
            )
        );
    }

    public function find_thread(
        int $threadid
    ): ?object {
        global $DB;

        $record = $DB->get_record_sql(
            "
            SELECT
                t.id,
                t.subject,
                t.status,
                t.priority,
                t.unreadcount,
                t.lastmessageat,
                t.assigneduserid,
                t.assignedteamid,

                c.id AS contactid,
                c.displayname AS contactname,
                c.primaryemail AS contactemail

              FROM {local_subscriptions_inbox_thread} t

         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid

             WHERE t.id = :threadid
               AND t.locallydeleted = 0
            ",
            [
                'threadid' => $threadid,
            ]
        );

        return $record ?: null;
    }

    /**
     * @return object[]
     */
    public function search_contacts(
        string $text,
        int $limit = 10
    ): array {
        global $DB;

        $text = trim($text);

        if (
            \core_text::strlen($text) < 2
        ) {
            return [];
        }

        $limit = max(
            1,
            min(20, $limit)
        );

        $query = '%' .
            $DB->sql_like_escape(
                \core_text::strtolower($text)
            ) .
            '%';

        $conditions = [
            $DB->sql_like(
                'LOWER(c.primaryemail)',
                ':contactemail',
                false
            ),

            $DB->sql_like(
                'LOWER(c.displayname)',
                ':contactname',
                false
            ),
        ];

        $sql = "
            SELECT
                c.id,
                c.displayname,
                c.primaryemail,
                c.matchstatus,

                COUNT(DISTINCT t.id)
                    AS conversationcount,

                COALESCE(
                    SUM(t.unreadcount),
                    0
                ) AS unreadcount,

                MAX(t.lastmessageat)
                    AS lastmessageat

              FROM {local_subscriptions_inbox_contact} c

         LEFT JOIN {local_subscriptions_inbox_thread} t
                ON t.contactid = c.id
               AND t.locallydeleted = 0

             WHERE (
                    " .
                    implode(
                        ' OR ',
                        $conditions
                    ) .
                    "
               )

          GROUP BY
                c.id,
                c.displayname,
                c.primaryemail,
                c.matchstatus

          ORDER BY
                lastmessageat DESC,
                c.displayname ASC,
                c.primaryemail ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'contactemail' => $query,
                    'contactname' => $query,
                ],
                0,
                $limit
            )
        );
    }

    public function find_contact(
        int $contactid
    ): ?object {
        global $DB;

        $record = $DB->get_record_sql(
            "
            SELECT
                c.id,
                c.displayname,
                c.primaryemail,
                c.matchstatus,

                COUNT(DISTINCT t.id)
                    AS conversationcount,

                COALESCE(
                    SUM(t.unreadcount),
                    0
                ) AS unreadcount,

                MAX(t.lastmessageat)
                    AS lastmessageat

              FROM {local_subscriptions_inbox_contact} c

         LEFT JOIN {local_subscriptions_inbox_thread} t
                ON t.contactid = c.id
               AND t.locallydeleted = 0

             WHERE c.id = :contactid

          GROUP BY
                c.id,
                c.displayname,
                c.primaryemail,
                c.matchstatus
            ",
            [
                'contactid' => $contactid,
            ]
        );

        return $record ?: null;
    }
}