<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxDiagnosticsRepository {

    public function table_exists(
        string $tablename
    ): bool {
        global $DB;

        return $DB->get_manager()->table_exists(
            new \xmldb_table($tablename)
        );
    }

    public function count(
        string $tablename,
        array $conditions = []
    ): int {
        global $DB;

        if (!$this->table_exists($tablename)) {
            return 0;
        }

        return (int)$DB->count_records(
            $tablename,
            $conditions
        );
    }

    public function latest_sync_log(
        int $accountid
    ): ?object {
        global $DB;

        $records = $DB->get_records(
            'local_subscriptions_inbox_sync_log',
            ['accountid' => $accountid],
            'startedat DESC, id DESC',
            '*',
            0,
            1
        );

        if (!$records) {
            return null;
        }

        return reset($records) ?: null;
    }

    public function failed_attachment_count(): int {
        return $this->count(
            'local_subscriptions_inbox_attachment',
            ['downloadstatus' => 'failed']
        );
    }

    public function pending_attachment_count(): int {
        return $this->count(
            'local_subscriptions_inbox_attachment',
            ['downloadstatus' => 'pending']
        );
    }

    public function unmatched_contact_count(): int {
        return $this->count(
            'local_subscriptions_inbox_contact',
            ['matchstatus' => 'unmatched']
        );
    }

    public function ambiguous_contact_count(): int {
        return $this->count(
            'local_subscriptions_inbox_contact',
            ['matchstatus' => 'ambiguous']
        );
    }

    public function duplicate_identity_count(
        int $accountid
    ): int {
        global $DB;

        $sql = "
            SELECT COUNT(1)
              FROM (
                    SELECT identitykey
                      FROM {local_subscriptions_inbox_message}
                     WHERE accountid = :accountid
                       AND identitykey IS NOT NULL
                       AND identitykey <> ''
                  GROUP BY identitykey
                    HAVING COUNT(1) > 1
                   ) duplicates
        ";

        return (int)$DB->count_records_sql(
            $sql,
            ['accountid' => $accountid]
        );
    }

    public function orphan_remote_count(
        int $accountid
    ): int {
        global $DB;

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(1)
                  FROM {local_subscriptions_inbox_remote} remote
             LEFT JOIN {local_subscriptions_inbox_message} message
                    ON message.id = remote.messageid
                 WHERE remote.accountid = :accountid
                   AND message.id IS NULL
            ",
            ['accountid' => $accountid]
        );
    }

    public function orphan_attachment_count(
        int $accountid
    ): int {
        global $DB;

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(1)
                  FROM {local_subscriptions_inbox_attachment} attachment
             LEFT JOIN {local_subscriptions_inbox_message} message
                    ON message.id = attachment.messageid
                 WHERE (
                        message.id IS NULL
                        OR message.accountid = :accountid
                           AND attachment.messageid IS NULL
                 )
            ",
            ['accountid' => $accountid]
        );
    }

    public function sent_copy_failure_count(
        int $accountid,
        int $since
    ): int {
        global $DB;

        $like = '%' .
            $DB->sql_like_escape(
                '"sentcopy"'
            ) .
            '%';

        $failure = '%' .
            $DB->sql_like_escape(
                '"success":false'
            ) .
            '%';

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(1)
                  FROM {local_subscriptions_inbox_message}
                 WHERE accountid = :accountid
                   AND direction = 'outbound'
                   AND timemodified >= :since
                   AND " . $DB->sql_like(
                       'headersjson',
                       ':sentcopy',
                       false
                   ) . "
                   AND " . $DB->sql_like(
                       'headersjson',
                       ':failure',
                       false
                   ) . "
            ",
            [
                'accountid' => $accountid,
                'since' => $since,
                'sentcopy' => $like,
                'failure' => $failure,
            ]
        );
    }

    public function last_successful_sync_at(
        int $accountid
    ): int {
        global $DB;

        $value = $DB->get_field_sql(
            "
                SELECT MAX(finishedat)
                  FROM {local_subscriptions_inbox_sync_log}
                 WHERE accountid = :accountid
                   AND status = 'success'
            ",
            ['accountid' => $accountid]
        );

        return max(0, (int)$value);
    }

}