<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxRetentionRepository {

    public function get_expired_thread_ids(
        int $closeddays,
        int $deletedays,
        int $limit
    ): array {
        global $DB;

        $closedthreshold =
            time() - ($closeddays * DAYSECS);

        $deletedthreshold =
            time() - ($deletedays * DAYSECS);

        $sql = "
            SELECT t.id
              FROM {local_subscriptions_inbox_thread} t
             WHERE (
                    t.status = :closedstatus
                AND t.lastmessageat < :closedthreshold
             )
                OR (
                    t.locallydeleted = 1
                AND t.timemodified < :deletedthreshold
             )
          ORDER BY t.id ASC
        ";

        return array_map(
            'intval',
            array_keys(
                $DB->get_records_sql(
                    $sql,
                    [
                        'closedstatus' => 'closed',
                        'closedthreshold' =>
                            $closedthreshold,
                        'deletedthreshold' =>
                            $deletedthreshold,
                    ],
                    0,
                    max(1, $limit)
                )
            )
        );
    }

    public function get_message_ids_for_thread(
        int $threadid
    ): array {
        global $DB;

        return array_map(
            'intval',
            array_keys(
                $DB->get_records(
                    'local_subscriptions_inbox_message',
                    ['threadid' => $threadid],
                    '',
                    'id'
                )
            )
        );
    }

    public function delete_thread(
        int $threadid
    ): void {
        global $DB;

        $messageids =
            $this->get_message_ids_for_thread(
                $threadid
            );

        $transaction =
            $DB->start_delegated_transaction();

        try {
            $DB->delete_records(
                'local_subscriptions_inbox_thread_tag',
                ['threadid' => $threadid]
            );

            if ($messageids) {
                [$insql, $params] =
                    $DB->get_in_or_equal(
                        $messageids,
                        SQL_PARAMS_NAMED,
                        'retentionmessage'
                    );

                $DB->delete_records_select(
                    'local_subscriptions_inbox_attachment',
                    "messageid {$insql}",
                    $params
                );

                $DB->delete_records_select(
                    'local_subscriptions_inbox_participant',
                    "messageid {$insql}",
                    $params
                );

                if (
                    $DB->get_manager()->table_exists(
                        new \xmldb_table(
                            'local_subscriptions_inbox_remote'
                        )
                    )
                ) {
                    $DB->delete_records_select(
                        'local_subscriptions_inbox_remote',
                        "messageid {$insql}",
                        $params
                    );
                }
            }

            $DB->delete_records(
                'local_subscriptions_inbox_message',
                ['threadid' => $threadid]
            );

            $DB->delete_records(
                'local_subscriptions_inbox_thread',
                ['id' => $threadid]
            );

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback(
                $exception
            );

            throw $exception;
        }
    }

    public function delete_old_sync_logs(
        int $days
    ): int {
        global $DB;

        return $DB->delete_records_select(
            'local_subscriptions_inbox_sync_log',
            'timecreated < :threshold',
            [
                'threshold' =>
                    time() - ($days * DAYSECS),
            ]
        );
    }

    public function delete_orphan_contacts(): int {
        global $DB;

        $sql = "
            DELETE FROM {local_subscriptions_inbox_contact}
             WHERE id NOT IN (
                    SELECT DISTINCT contactid
                      FROM {local_subscriptions_inbox_thread}
                     WHERE contactid IS NOT NULL
             )
               AND id NOT IN (
                    SELECT DISTINCT contactid
                      FROM {local_subscriptions_inbox_participant}
                     WHERE contactid IS NOT NULL
             )
        ";

        return $DB->execute($sql) ? 1 : 0;
    }

    /**
     * @return int[]
     */
    public function get_file_item_ids_for_thread(
        int $threadid
    ): array {
        global $DB;

        $sql = "
            SELECT DISTINCT attachment.fileitemid

              FROM {local_subscriptions_inbox_attachment}
                   attachment

              JOIN {local_subscriptions_inbox_message}
                   message
                ON message.id = attachment.messageid

             WHERE message.threadid = :threadid
               AND attachment.fileitemid IS NOT NULL
               AND attachment.fileitemid > 0
        ";

        return array_values(
            array_map(
                'intval',
                array_keys(
                    $DB->get_records_sql(
                        $sql,
                        [
                            'threadid' =>
                                $threadid,
                        ]
                    )
                )
            )
        );
    }

}