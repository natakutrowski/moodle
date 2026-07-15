<?php

namespace local_subscriptions\crm\intelligence\inbox;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceInboxRepository {

    /**
     * @param int[] $userids
     * @return array<int, \stdClass>
     */
    public function get_by_userids(
        array $userids
    ): array {
        global $DB;

        $userids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $userids
                    ),
                    static fn(int $userid): bool =>
                        $userid > 0
                )
            )
        );

        if (!$userids) {
            return [];
        }

        [$insql, $params] =
            $DB->get_in_or_equal(
                $userids,
                SQL_PARAMS_NAMED,
                'inboxuserid'
            );

        $records = $DB->get_records_sql(
            "
            SELECT
                contact.matcheduserid AS userid,

                COUNT(thread.id)
                    AS conversationcount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN thread.status = 'open'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS openconversationcount,

                COALESCE(
                    SUM(thread.unreadcount),
                    0
                ) AS unreadcount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN thread.priority = 'urgent'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS urgentcount,

                MAX(thread.lastmessageat)
                    AS lastmessageat

              FROM {local_subscriptions_inbox_contact}
                   contact

              JOIN {local_subscriptions_inbox_thread}
                   thread
                ON thread.contactid = contact.id
               AND thread.locallydeleted = 0

             WHERE contact.matcheduserid {$insql}

          GROUP BY contact.matcheduserid
            ",
            $params
        );

        $result = [];

        foreach ($records as $record) {
            $result[(int)$record->userid] =
                $record;
        }

        return $result;
    }
}