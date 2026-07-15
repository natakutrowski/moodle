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
}