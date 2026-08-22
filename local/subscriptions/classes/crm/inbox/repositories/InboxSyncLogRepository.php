<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxSyncLogRepository {

    private const TABLE =
        'local_subscriptions_inbox_sync_log';

    public function start(
        int $accountid,
        string $synctype,
        ?string $folder,
        ?string $cursorbefore
    ): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'accountid' => $accountid,
                'synctype' => $synctype,
                'status' => 'running',
                'folder' => $folder,
                'cursorbefore' => $cursorbefore,
                'cursorafter' => null,
                'fetchedcount' => 0,
                'createdcount' => 0,
                'updatedcount' => 0,
                'skippedcount' => 0,
                'errorcount' => 0,
                'message' => null,
                'startedat' => $now,
                'finishedat' => null,
                'timecreated' => $now,
            ]
        );
    }

    public function complete(
        int $logid,
        string $status,
        ?string $cursorafter,
        int $fetched,
        int $created,
        int $updated,
        int $skipped,
        int $errors,
        ?string $message = null
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $logid,
                'status' => $status,
                'cursorafter' => $cursorafter,
                'fetchedcount' => $fetched,
                'createdcount' => $created,
                'updatedcount' => $updated,
                'skippedcount' => $skipped,
                'errorcount' => $errors,
                'message' => $message,
                'finishedat' => time(),
            ]
        );
    }

    /**
     * @return object[]
     */
    public function recent(
        int $accountid,
        int $limit = 12
    ): array {
        global $DB;

        return array_values(
            $DB->get_records(
                self::TABLE,
                ['accountid' => $accountid],
                'startedat DESC, id DESC',
                '*',
                0,
                max(1, min(50, $limit))
            )
        );
    }

    public function count_status_since(
        int $accountid,
        array $statuses,
        int $since
    ): int {
        global $DB;

        if ($statuses === []) {
            return 0;
        }

        [$insql, $params] = $DB->get_in_or_equal(
            array_values(
                array_unique($statuses)
            ),
            SQL_PARAMS_NAMED,
            'syncstatus'
        );

        $params['accountid'] = $accountid;
        $params['since'] = $since;

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(1)
                  FROM {" . self::TABLE . "}
                 WHERE accountid = :accountid
                   AND status {$insql}
                   AND startedat >= :since
            ",
            $params
        );
    }

    public function stale_running_count(
        int $accountid,
        int $olderthan
    ): int {
        global $DB;

        return (int)$DB->count_records_select(
            self::TABLE,
            'accountid = :accountid
             AND status = :status
             AND startedat < :olderthan',
            [
                'accountid' => $accountid,
                'status' => 'running',
                'olderthan' => $olderthan,
            ]
        );
    }

    public function close_stale_running(
        int $accountid,
        int $olderthan
    ): int {
        global $DB;

        $records = $DB->get_records_select(
            self::TABLE,
            'accountid = :accountid
             AND status = :status
             AND startedat < :olderthan',
            [
                'accountid' => $accountid,
                'status' => 'running',
                'olderthan' => $olderthan,
            ],
            'startedat ASC',
            'id'
        );

        foreach ($records as $record) {
            $DB->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$record->id,
                    'status' => 'failed',
                    'errorcount' => 1,
                    'message' =>
                        'Stale running synchronisation recovered by O14 hardening.',
                    'finishedat' => time(),
                ]
            );
        }

        return count($records);
    }

}