<?php

namespace local_subscriptions\crm\user\inbox;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;

final class UserInboxRepository {

    private const CONTACT_TABLE =
        'local_subscriptions_inbox_contact';

    private const THREAD_TABLE =
        'local_subscriptions_inbox_thread';

    private const MESSAGE_TABLE =
        'local_subscriptions_inbox_message';

    private const AI_RESULT_TABLE =
        'local_subscriptions_inbox_ai_result';

    public function is_available(): bool {
        global $DB;

        $manager = $DB->get_manager();

        return
            $manager->table_exists(
                self::CONTACT_TABLE
            ) &&
            $manager->table_exists(
                self::THREAD_TABLE
            ) &&
            $manager->table_exists(
                self::MESSAGE_TABLE
            );
    }

    public function get_summary(
        int $userid
    ): \stdClass {
        global $DB;

        $record = $DB->get_record_sql(
            "
            SELECT
                COUNT(t.id)
                    AS conversationcount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN t.status = :statusopen
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS openconversationcount,

                COALESCE(
                    SUM(t.unreadcount),
                    0
                ) AS unreadcount,

                MAX(t.lastmessageat)
                    AS lastmessageat

              FROM {" . self::CONTACT_TABLE . "} c

              JOIN {" . self::THREAD_TABLE . "} t
                ON t.contactid = c.id
               AND t.locallydeleted = 0

             WHERE c.matcheduserid = :userid
            ",
            [
                'userid' => $userid,
                'statusopen' => 'open',
            ]
        );

        if (!$record) {
            return (object)[
                'conversationcount' => 0,
                'openconversationcount' => 0,
                'unreadcount' => 0,
                'lastmessageat' => null,
            ];
        }

        return $record;
    }

    /**
     * @return object[]
     */
    public function get_recent_threads(
        int $userid,
        int $limit = 5
    ): array {
        global $DB;

        $limit = max(
            1,
            min(10, $limit)
        );

        return array_values(
            $DB->get_records_sql(
                "
                SELECT
                    t.id,
                    t.subject,
                    t.status,
                    t.priority,
                    t.unreadcount,
                    t.messagecount,
                    t.lastmessageat,
                    t.lastmessageid,
                    t.assigneduserid,
                    t.assignedteamid,

                    c.id AS contactid,
                    c.displayname AS contactname,
                    c.primaryemail AS contactemail,

                    m.direction AS lastdirection,
                    m.status AS lastmessagestatus,
                    m.subject AS lastmessagesubject,
                    m.receivedat AS lastreceivedat,
                    m.sentat AS lastsentat

                  FROM {" . self::THREAD_TABLE . "} t

                  JOIN {" . self::CONTACT_TABLE . "} c
                    ON c.id = t.contactid

             LEFT JOIN {" . self::MESSAGE_TABLE . "} m
                    ON m.id = t.lastmessageid

                 WHERE c.matcheduserid = :userid
                   AND t.locallydeleted = 0

              ORDER BY
                    t.lastmessageat DESC,
                    t.id DESC
                ",
                [
                    'userid' => $userid,
                ],
                0,
                $limit
            )
        );
    }

    public function count_ai_reply_suggestions(
        int $userid
    ): int {
        global $DB;

        if (
            !$DB->get_manager()->table_exists(
                self::AI_RESULT_TABLE
            )
        ) {
            return 0;
        }

        return (int)$DB->count_records_sql(
            "
            SELECT COUNT(ar.id)

              FROM {" . self::AI_RESULT_TABLE . "} ar

              JOIN {" . self::THREAD_TABLE . "} t
                ON t.id = ar.threadid
               AND t.locallydeleted = 0

              JOIN {" . self::CONTACT_TABLE . "} c
                ON c.id = t.contactid

             WHERE c.matcheduserid = :userid
               AND ar.capability = :capability
               AND ar.status IN (
                    :success,
                    :partial
               )

               AND NOT EXISTS (
                    SELECT 1

                      FROM {" . self::AI_RESULT_TABLE . "} newer

                     WHERE newer.threadid = ar.threadid
                       AND newer.capability = ar.capability
                       AND (
                            newer.timecreated > ar.timecreated
                            OR (
                                newer.timecreated = ar.timecreated
                                AND newer.id > ar.id
                            )
                       )
               )
            ",
            [
                'userid' =>
                    $userid,

                'capability' =>
                    InboxAiCapability::
                        REPLY_SUGGESTION,

                'success' =>
                    InboxAiStatus::SUCCESS,

                'partial' =>
                    InboxAiStatus::PARTIAL,
            ]
        );
    }
}