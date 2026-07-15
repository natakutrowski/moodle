<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAttachmentStatus;
use local_subscriptions\crm\inbox\dto\InboxAttachmentData;

final class InboxAttachmentRepository {

    private const TABLE =
        'local_subscriptions_inbox_attachment';

    public function find(int $id): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record ?: null;
    }

    /**
     * @return object[]
     */
    public function get_for_message(
        int $messageid
    ): array {
        global $DB;

        return array_values(
            $DB->get_records(
                self::TABLE,
                ['messageid' => $messageid],
                'id ASC'
            )
        );
    }

    public function get_or_create(
        int $messageid,
        InboxAttachmentData $attachment,
        string $stableproviderid
    ): object {
        global $DB;

        $existing = $DB->get_record(
            self::TABLE,
            [
                'messageid' => $messageid,
                'providerattachmentid' =>
                    $stableproviderid,
            ]
        );

        if ($existing) {
            return $existing;
        }

        $now = time();

        $id = $DB->insert_record(
            self::TABLE,
            (object)[
                'messageid' => $messageid,
                'providerattachmentid' =>
                    $stableproviderid,
                'filename' =>
                    clean_param(
                        $attachment->filename,
                        PARAM_FILE
                    ),
                'mimetype' =>
                    $attachment->mimetype,
                'filesize' =>
                    max(0, $attachment->filesize),
                'contenthash' => null,
                'fileitemid' => null,
                'downloadstatus' =>
                    InboxAttachmentStatus::PENDING,
                'lasterror' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return $this->find((int)$id);
    }

    /**
     * @return object[]
     */
    public function get_pending(
        int $limit = 100
    ): array {
        global $DB;

        return array_values(
            $DB->get_records(
                self::TABLE,
                [
                    'downloadstatus' =>
                        InboxAttachmentStatus::PENDING,
                ],
                'timecreated ASC, id ASC',
                '*',
                0,
                max(1, $limit)
            )
        );
    }

    public function mark_downloading(
        int $attachmentid
    ): void {
        $this->update_status(
            $attachmentid,
            InboxAttachmentStatus::DOWNLOADING
        );
    }

    public function mark_stored(
        int $attachmentid,
        int $fileitemid,
        string $contenthash,
        int $filesize
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $attachmentid,
                'fileitemid' => $fileitemid,
                'contenthash' => $contenthash,
                'filesize' => max(0, $filesize),
                'downloadstatus' =>
                    InboxAttachmentStatus::STORED,
                'lasterror' => null,
                'timemodified' => time(),
            ]
        );
    }

    public function mark_failed(
        int $attachmentid,
        string $message
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $attachmentid,
                'downloadstatus' =>
                    InboxAttachmentStatus::FAILED,
                'lasterror' => $message,
                'timemodified' => time(),
            ]
        );
    }

    public function reset_pending(
        int $attachmentid
    ): void {
        $this->update_status(
            $attachmentid,
            InboxAttachmentStatus::PENDING
        );
    }

    private function update_status(
        int $attachmentid,
        string $status
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $attachmentid,
                'downloadstatus' => $status,
                'lasterror' => null,
                'timemodified' => time(),
            ]
        );
    }

    public function get_pending_with_source(
        int $limit = 100
    ): array {
        global $DB;

        $sql = "
            SELECT
                attachment.*,

                remote.accountid,
                remote.folder,
                remote.provideruid,
                remote.uidvalidity,
                remote.providerkey

              FROM {local_subscriptions_inbox_attachment}
                   attachment

              JOIN {local_subscriptions_inbox_message}
                   message
                ON message.id = attachment.messageid

              JOIN {local_subscriptions_inbox_remote}
                   remote
                ON remote.messageid = message.id
               AND remote.active = 1

              JOIN {local_subscriptions_inbox_thread}
                   thread
                ON thread.id = message.threadid
               AND thread.locallydeleted = 0

             WHERE attachment.downloadstatus IN (
                    :pendingstatus,
                    :failedstatus
             )
               AND remote.provideruid <> ''
               AND remote.folder <> ''

          ORDER BY
                attachment.timemodified ASC,
                attachment.id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'pendingstatus' =>
                        InboxAttachmentStatus::PENDING,

                    'failedstatus' =>
                        InboxAttachmentStatus::FAILED,
                ],
                0,
                max(
                    1,
                    min(500, $limit)
                )
            )
        );
    }

}