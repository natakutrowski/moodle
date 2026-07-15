<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadActionRepository {

    private const THREAD_TABLE =
        'local_subscriptions_inbox_thread';

    private const MESSAGE_TABLE =
        'local_subscriptions_inbox_message';

    public function set_status(
        int $threadid,
        string $status
    ): void {
        global $DB;

        $DB->update_record(
            self::THREAD_TABLE,
            (object)[
                'id' => $threadid,
                'status' => $status,
                'timemodified' => time(),
            ]
        );
    }

    public function set_priority(
        int $threadid,
        string $priority
    ): void {
        global $DB;

        $DB->update_record(
            self::THREAD_TABLE,
            (object)[
                'id' => $threadid,
                'priority' => $priority,
                'timemodified' => time(),
            ]
        );
    }

    public function mark_read(
        int $threadid,
        bool $read
    ): void {
        global $DB;

        $DB->set_field(
            self::MESSAGE_TABLE,
            'isread',
            $read ? 1 : 0,
            ['threadid' => $threadid]
        );

        $DB->update_record(
            self::THREAD_TABLE,
            (object)[
                'id' => $threadid,
                'unreadcount' => $read
                    ? 0
                    : $this->message_count($threadid),
                'timemodified' => time(),
            ]
        );
    }

    public function mark_locally_deleted(
        int $threadid
    ): void {
        global $DB;

        $DB->update_record(
            self::THREAD_TABLE,
            (object)[
                'id' => $threadid,
                'locallydeleted' => 1,
                'timemodified' => time(),
            ]
        );
    }

    public function set_thread_folder(
        int $threadid,
        string $folder
    ): void {
        global $DB;

        $DB->update_record(
            self::THREAD_TABLE,
            (object)[
                'id' => $threadid,
                'folder' => $folder,
                'timemodified' => time(),
            ]
        );
    }

    private function message_count(
        int $threadid
    ): int {
        global $DB;

        return (int)$DB->count_records(
            self::MESSAGE_TABLE,
            ['threadid' => $threadid]
        );
    }
}