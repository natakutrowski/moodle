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
         LEFT JOIN {local_subscriptions_inbox_account} account
                ON account.id = t.accountid
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
                account.name AS accountname,
                account.email AS accountemail,
                c.displayname AS contactname,
                c.primaryemail AS contactemail,
                c.matcheduserid,
                c.matchstatus,
                au.firstname AS assignedfirstname,
                au.lastname AS assignedlastname,
                team.name AS assignedteamname,
                lastmessage.bodytext AS lastbodytext,
                lastmessage.bodyhtml AS lastbodyhtml,
                lastmessage.direction AS lastdirection,
                lastmessage.status AS lastmessagestatus
              FROM {local_subscriptions_inbox_thread} t
         LEFT JOIN {local_subscriptions_inbox_account} account
                ON account.id = t.accountid
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

    public function get_last_inbound_message(
        int $threadid
    ): ?object {
        global $DB;

        $record = $DB->get_record_sql(
            "
                SELECT *
                  FROM {local_subscriptions_inbox_message}
                 WHERE threadid = :threadid
                   AND direction = 'inbound'
              ORDER BY
                    COALESCE(receivedat, sentat, timecreated) DESC,
                    id DESC
            ",
            ['threadid' => $threadid],
            IGNORE_MULTIPLE
        );

        return $record ?: null;
    }

    /**
     * @return object[]
     */
    public function get_participants_for_message(
        int $messageid
    ): array {
        return $this->get_participants_by_message(
            [$messageid]
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

        $conditions = [];
        $params = [];

        $this->apply_folder_condition(
            $criteria,
            $conditions,
            $params
        );

        if ($criteria->query !== '') {
            $query = '%' .
                $DB->sql_like_escape(
                    \core_text::strtolower(
                        $criteria->query
                    )
                ) .
                '%';

            $conditions[] = "(
                " . $DB->sql_like(
                    'LOWER(' .
                    $DB->sql_cast_to_char(
                        't.subject'
                    ) . ')',
                    ':querysubject',
                    false
                ) . "
                OR " . $DB->sql_like(
                    'LOWER(c.primaryemail)',
                    ':queryemail',
                    false
                ) . "
                OR " . $DB->sql_like(
                    'LOWER(c.displayname)',
                    ':queryname',
                    false
                ) . "
                OR " . $DB->sql_like(
                    'LOWER(account.email)',
                    ':queryaccountemail',
                    false
                ) . "
                OR EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} searchmessage
                     WHERE searchmessage.threadid = t.id
                       AND (
                            " . $DB->sql_like(
                                'LOWER(' .
                                $DB->sql_cast_to_char(
                                    'searchmessage.subject'
                                ) . ')',
                                ':querymessagesubject',
                                false
                            ) . "
                            OR " . $DB->sql_like(
                                'LOWER(' .
                                $DB->sql_cast_to_char(
                                    'searchmessage.bodytext'
                                ) . ')',
                                ':querybodytext',
                                false
                            ) . "
                       )
                )
                OR EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} participantmessage
                      JOIN {local_subscriptions_inbox_participant} participant
                        ON participant.messageid = participantmessage.id
                     WHERE participantmessage.threadid = t.id
                       AND (
                            " . $DB->sql_like(
                                'LOWER(participant.email)',
                                ':queryparticipantemail',
                                false
                            ) . "
                            OR " . $DB->sql_like(
                                'LOWER(participant.displayname)',
                                ':queryparticipantname',
                                false
                            ) . "
                       )
                )
                OR EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} attachmentmessage
                      JOIN {local_subscriptions_inbox_attachment} attachment
                        ON attachment.messageid = attachmentmessage.id
                     WHERE attachmentmessage.threadid = t.id
                       AND " . $DB->sql_like(
                            'LOWER(attachment.filename)',
                            ':queryattachment',
                            false
                       ) . "
                )
            )";

            foreach (
                [
                    'querysubject',
                    'queryemail',
                    'queryname',
                    'queryaccountemail',
                    'querymessagesubject',
                    'querybodytext',
                    'queryparticipantemail',
                    'queryparticipantname',
                    'queryattachment',
                ]
                as $parameter
            ) {
                $params[$parameter] = $query;
            }
        }

        if ($criteria->status !== '') {
            $conditions[] = 't.status = :status';
            $params['status'] = $criteria->status;
        }

        if ($criteria->priority !== '') {
            $conditions[] = 't.priority = :priority';
            $params['priority'] = $criteria->priority;
        }

        if (
            $criteria->unreadonly
            && $criteria->readstate === ''
        ) {
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

        if ($criteria->accountid > 0) {
            $conditions[] =
                't.accountid = :filteraccountid';
            $params['filteraccountid'] =
                $criteria->accountid;
        }

        if ($criteria->readstate === 'unread') {
            $conditions[] = 't.unreadcount > 0';
        } else if (
            $criteria->readstate === 'read'
        ) {
            $conditions[] = 't.unreadcount = 0';
        }

        if (
            $criteria->attachmentstate === 'with'
        ) {
            $conditions[] =
                "EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} attachmentfiltermessage
                      JOIN {local_subscriptions_inbox_attachment} attachmentfilter
                        ON attachmentfilter.messageid = attachmentfiltermessage.id
                     WHERE attachmentfiltermessage.threadid = t.id
                )";
        } else if (
            $criteria->attachmentstate === 'without'
        ) {
            $conditions[] =
                "NOT EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} attachmentfiltermessage
                      JOIN {local_subscriptions_inbox_attachment} attachmentfilter
                        ON attachmentfilter.messageid = attachmentfiltermessage.id
                     WHERE attachmentfiltermessage.threadid = t.id
                )";
        }

        if ($criteria->period === 'custom') {
            [$customstart, $customend] =
                $this->custom_period_bounds(
                    $criteria->datefrom,
                    $criteria->dateto
                );

            if ($customstart !== null) {
                $conditions[] =
                    't.lastmessageat >= :periodstart';
                $params['periodstart'] =
                    $customstart;
            }

            if ($customend !== null) {
                $conditions[] =
                    't.lastmessageat <= :periodend';
                $params['periodend'] =
                    $customend;
            }
        } else {
            $periodstart =
                $this->period_start(
                    $criteria->period
                );

            if ($periodstart !== null) {
                $conditions[] =
                    't.lastmessageat >= :periodstart';
                $params['periodstart'] =
                    $periodstart;
            }
        }

        if ($criteria->direction === 'draft') {
            $conditions[] =
                "(
                    t.folder = 'DRAFTS'
                    OR EXISTS (
                        SELECT 1
                          FROM {local_subscriptions_inbox_message} directionmessage
                         WHERE directionmessage.id = t.lastmessageid
                           AND directionmessage.status = 'draft'
                    )
                )";
        } else if (
            $criteria->direction === 'inbound'
        ) {
            $conditions[] =
                "EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} directionmessage
                     WHERE directionmessage.id = t.lastmessageid
                       AND directionmessage.direction = 'inbound'
                       AND directionmessage.status <> 'draft'
                )";
        } else if (
            $criteria->direction === 'outbound'
        ) {
            $conditions[] =
                "EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} directionmessage
                     WHERE directionmessage.id = t.lastmessageid
                       AND directionmessage.direction = 'outbound'
                       AND directionmessage.status <> 'draft'
                )";
        }

        return [
            implode(' AND ', $conditions),
            $params,
        ];
    }

    /**
     * @param string[] $conditions
     * @param array<string,mixed> $params
     */
    private function apply_folder_condition(
        InboxThreadCriteria $criteria,
        array &$conditions,
        array &$params
    ): void {
        global $DB;

        if ($criteria->folder === 'trash') {
            $names = $this->configured_folder_names(
                'trash',
                $criteria->accountid
            );

            if ($names === []) {
                $conditions[] = 't.locallydeleted = 1';
                return;
            }

            [$insql, $inparams] = $DB->get_in_or_equal(
                $names,
                SQL_PARAMS_NAMED,
                'trashfolder'
            );

            $conditions[] =
                "(t.locallydeleted = 1 OR t.folder {$insql})";
            $params += $inparams;
            return;
        }

        $conditions[] = 't.locallydeleted = 0';

        if ($criteria->folder === 'all') {
            return;
        }

        if ($criteria->folder === 'drafts') {
            $conditions[] =
                "(
                    t.folder = 'DRAFTS'
                    OR EXISTS (
                        SELECT 1
                          FROM {local_subscriptions_inbox_message} foldermessage
                         WHERE foldermessage.id = t.lastmessageid
                           AND foldermessage.status = 'draft'
                    )
                )";
            return;
        }

        if ($criteria->folder === 'sent') {
            $names = $this->configured_folder_names(
                'sent',
                $criteria->accountid
            );

            $sentcondition =
                "EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} foldermessage
                     WHERE foldermessage.id = t.lastmessageid
                       AND foldermessage.direction = 'outbound'
                       AND foldermessage.status <> 'draft'
                )";

            if ($names !== []) {
                [$insql, $inparams] = $DB->get_in_or_equal(
                    $names,
                    SQL_PARAMS_NAMED,
                    'sentfolder'
                );

                $sentcondition =
                    "(t.folder {$insql} OR {$sentcondition})";
                $params += $inparams;
            }

            $conditions[] = $sentcondition;
            return;
        }

        $names = $this->configured_folder_names(
            $criteria->folder,
            $criteria->accountid
        );

        if (
            $criteria->folder === 'inbox'
            && !in_array('INBOX', $names, true)
        ) {
            $names[] = 'INBOX';
        }

        if ($names === []) {
            $conditions[] = '1 = 0';
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal(
            array_values(array_unique($names)),
            SQL_PARAMS_NAMED,
            'folder' . $criteria->folder
        );

        $conditions[] = "t.folder {$insql}";
        $params += $inparams;
    }

    /**
     * @return string[]
     */
    private function configured_folder_names(
        string $type,
        int $accountid = 0
    ): array {
        global $DB;

        $conditions = ['enabled' => 1];

        if ($accountid > 0) {
            $conditions['id'] = $accountid;
        }

        $records = $DB->get_records(
            'local_subscriptions_inbox_account',
            $conditions,
            'id ASC',
            'id,configurationjson'
        );

        $names = [];

        foreach ($records as $record) {
            $configuration = json_decode(
                (string)($record->configurationjson ?? ''),
                true
            );

            if (!is_array($configuration)) {
                continue;
            }

            $name = trim(
                (string)(
                    $configuration['folders'][$type]
                    ?? ''
                )
            );

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(
            array_unique($names)
        );
    }


    /**
     * Resolve an inclusive custom date range in the current user's timezone.
     *
     * @return array{0:?int,1:?int}
     */
    private function custom_period_bounds(
        string $datefrom,
        string $dateto
    ): array {
        $start = null;
        $end = null;

        if ($datefrom !== '') {
            [$year, $month, $day] = array_map(
                'intval',
                explode('-', $datefrom)
            );
            $start = make_timestamp(
                $year,
                $month,
                $day,
                0,
                0,
                0
            );
        }

        if ($dateto !== '') {
            [$year, $month, $day] = array_map(
                'intval',
                explode('-', $dateto)
            );
            $end = make_timestamp(
                $year,
                $month,
                $day,
                23,
                59,
                59
            );
        }

        if (
            $start !== null
            && $end !== null
            && $start > $end
        ) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function period_start(
        string $period
    ): ?int {
        $now = time();

        return match ($period) {
            'today' => usergetmidnight($now),
            '7days' => $now - (7 * DAYSECS),
            '30days' => $now - (30 * DAYSECS),
            '90days' => $now - (90 * DAYSECS),
            default => null,
        };
    }
}