<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxThreadCriteria;

final class InboxReadRepository {

    public function count_threads(
        InboxThreadCriteria $criteria,
        int $actorid
    ): int {
        global $DB;

        [$where, $params] = $this->build_where(
            $criteria,
            $actorid
        );

        $sql = "
            SELECT COUNT(1)
              FROM {local_subscriptions_inbox_thread} t
         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid
             WHERE {$where}
        ";

        return (int)$DB->count_records_sql(
            $sql,
            $params
        );
    }

    /**
     * @return object[]
     */
    public function get_threads(
        InboxThreadCriteria $criteria,
        int $actorid
    ): array {
        global $DB;

        [$where, $params] = $this->build_where(
            $criteria,
            $actorid
        );

        $sql = "
            SELECT
                t.id,
                t.accountid,
                t.contactid,
                t.subject,
                t.status,
                t.priority,
                t.assigneduserid,
                t.assignedteamid,
                t.folder,
                t.unreadcount,
                t.messagecount,
                t.lastmessageat,
                c.displayname AS contactname,
                c.primaryemail AS contactemail,
                c.matcheduserid,
                c.matchstatus,
                au.firstname AS assignedfirstname,
                au.lastname AS assignedlastname,
                team.name AS assignedteamname,
                lastmessage.bodytext AS lastbodytext,
                lastmessage.bodyhtml AS lastbodyhtml
              FROM {local_subscriptions_inbox_thread} t
         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid
         LEFT JOIN {user} au
                ON au.id = t.assigneduserid
         LEFT JOIN {local_subscriptions_inbox_team} team
                ON team.id = t.assignedteamid
        LEFT JOIN {local_subscriptions_inbox_message} lastmessage
            ON lastmessage.id = t.lastmessageid
             WHERE {$where}
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
                $params,
                $criteria->offset(),
                $criteria->perpage
            )
        );
    }

    public function get_thread(int $threadid): ?object {
        global $DB;

        $sql = "
            SELECT
                t.*,
                a.name AS accountname,
                a.email AS accountemail,
                c.displayname AS contactname,
                c.primaryemail AS contactemail,
                c.normalizedemail,
                c.matcheduserid,
                c.matchstatus,
                c.matchsource,
                c.matchconfidence,
                c.matchlocked,
                au.firstname AS assignedfirstname,
                au.lastname AS assignedlastname,
                team.name AS assignedteamname,
                mu.firstname AS matchedfirstname,
                mu.lastname AS matchedlastname,
                mu.email AS matchedemail
              FROM {local_subscriptions_inbox_thread} t
              JOIN {local_subscriptions_inbox_account} a
                ON a.id = t.accountid
         LEFT JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid
         LEFT JOIN {user} au
                ON au.id = t.assigneduserid
         LEFT JOIN {local_subscriptions_inbox_team} team
                ON team.id = t.assignedteamid
         LEFT JOIN {user} mu
                ON mu.id = c.matcheduserid
             WHERE t.id = :threadid
               AND t.locallydeleted = 0
        ";

        $record = $DB->get_record_sql(
            $sql,
            ['threadid' => $threadid]
        );

        return $record ?: null;
    }

    /**
     * @return object[]
     */
    public function get_messages(
        int $threadid
    ): array {
        global $DB;

        return array_values(
            $DB->get_records(
                'local_subscriptions_inbox_message',
                ['threadid' => $threadid],
                '
                    COALESCE(receivedat, sentat, timecreated) ASC,
                    id ASC
                '
            )
        );
    }

    /**
     * @return object[]
     */
    public function get_participants_by_message(
        array $messageids
    ): array {
        global $DB;

        if (!$messageids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(
            $messageids,
            SQL_PARAMS_NAMED,
            'messageid'
        );

        $sql = "
            SELECT *
              FROM {local_subscriptions_inbox_participant}
             WHERE messageid {$insql}
          ORDER BY messageid ASC,
                   participanttype ASC,
                   id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                $params
            )
        );
    }

    /**
     * @return object[]
     */
    public function get_attachments_by_message(
        array $messageids
    ): array {
        global $DB;

        if (!$messageids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(
            $messageids,
            SQL_PARAMS_NAMED,
            'attachmentmessage'
        );

        $sql = "
            SELECT *
              FROM {local_subscriptions_inbox_attachment}
             WHERE messageid {$insql}
          ORDER BY messageid ASC, id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                $params
            )
        );
    }

    /**
     * @return object[]
     */
    public function get_remote_messages_for_thread(
        int $threadid
    ): array {
        global $DB;

        $sql = "
            SELECT
                remote.id AS remoteid,
                remote.messageid,
                remote.accountid,
                remote.folder,
                remote.uidvalidity,
                remote.provideruid,
                remote.providerkey,

                message.direction,
                message.status

              FROM {local_subscriptions_inbox_remote} remote

              JOIN {local_subscriptions_inbox_message} message
                ON message.id = remote.messageid

             WHERE message.threadid = :threadid
               AND remote.active = 1
               AND remote.provideruid <> ''
               AND remote.folder <> ''

          ORDER BY
                message.id ASC,
                remote.id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'threadid' => $threadid,
                ]
            )
        );
    }

    private function build_where(
        InboxThreadCriteria $criteria,
        int $actorid
    ): array {
        global $DB;

        $conditions = [
            't.locallydeleted = 0',
        ];

        $params = [];

        if ($criteria->query !== '') {
            $query = '%' .
                $DB->sql_like_escape(
                    \core_text::strtolower(
                        $criteria->query
                    )
                ) .
                '%';

            $searchconditions = [
                $DB->sql_like(
                    'LOWER(' . $DB->sql_cast_to_char('t.subject') . ')',
                    ':querysubject',
                    false
                ),
                $DB->sql_like(
                    'LOWER(c.primaryemail)',
                    ':queryemail',
                    false
                ),
                $DB->sql_like(
                    'LOWER(c.displayname)',
                    ':queryname',
                    false
                ),
            ];

            $conditions[] =
                '(' .
                implode(' OR ', $searchconditions) .
                ')';

            $params['querysubject'] = $query;
            $params['queryemail'] = $query;
            $params['queryname'] = $query;
        }

        if ($criteria->status !== '') {
            $conditions[] = 't.status = :status';
            $params['status'] = $criteria->status;
        }

        if ($criteria->priority !== '') {
            $conditions[] = 't.priority = :priority';
            $params['priority'] = $criteria->priority;
        }

        if ($criteria->unreadonly) {
            $conditions[] = 't.unreadcount > 0';
        }

        if ($criteria->assignment === 'mine') {
            $conditions[] =
                't.assigneduserid = :assignedactor';

            $params['assignedactor'] = $actorid;
        } else if (
            $criteria->assignment === 'unassigned'
        ) {
            $conditions[] =
                't.assigneduserid IS NULL';
            $conditions[] =
                't.assignedteamid IS NULL';
        } else if (
            $criteria->assignment === 'team'
        ) {
            $conditions[] =
                't.assignedteamid IS NOT NULL';
        }

        if ($criteria->teamid > 0) {
            $conditions[] =
                't.assignedteamid = :teamid';

            $params['teamid'] = $criteria->teamid;
        }

        if ($criteria->match === 'matched') {
            $conditions[] =
                'c.matcheduserid IS NOT NULL';
        } else if (
            $criteria->match === 'unmatched'
        ) {
            $conditions[] =
                'c.matcheduserid IS NULL';
            $conditions[] =
                "c.matchstatus = 'unmatched'";
        } else if (
            $criteria->match === 'ambiguous'
        ) {
            $conditions[] =
                "c.matchstatus = 'ambiguous'";
        }

        return [
            implode(' AND ', $conditions),
            $params,
        ];
    }
}