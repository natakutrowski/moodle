<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadRepository {

    private const TABLE =
        'local_subscriptions_inbox_thread';

    public function find(int $id): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record ?: null;
    }

    public function find_by_provider_thread(
        int $accountid,
        string $providerthreadid
    ): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'accountid' => $accountid,
                'providerthreadid' => $providerthreadid,
            ]
        );

        return $record ?: null;
    }

    public function create(
        int $accountid,
        ?int $contactid,
        string $providerthreadid,
        ?string $subject,
        string $folder,
        int $messageat,
        bool $unread
    ): object {
        global $DB;

        $now = time();

        $id = $DB->insert_record(
            self::TABLE,
            (object)[
                'accountid' => $accountid,
                'contactid' => $contactid,
                'providerthreadid' => $providerthreadid,
                'subject' => $subject,
                'status' => 'open',
                'priority' => 'normal',
                'assigneduserid' => null,
                'assignedteamid' => null,
                'folder' => $folder,
                'unreadcount' => $unread ? 1 : 0,
                'messagecount' => 0,
                'lastmessageat' => $messageat,
                'locallydeleted' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return $this->find((int)$id);
    }

    public function update_after_message(
        int $threadid,
        ?int $contactid,
        ?string $subject,
        string $folder,
        int $messageat,
        bool $unread,
        bool $incrementmessage = true,
        ?int $lastmessageid = null
    ): void {
        global $DB;

        $thread = $DB->get_record(
            self::TABLE,
            ['id' => $threadid],
            '*',
            MUST_EXIST
        );

        $record = (object)[
            'id' => $threadid,
            'folder' => $folder,
            'lastmessageat' => max(
                (int)($thread->lastmessageat ?? 0),
                $messageat
            ),
            'timemodified' => time(),
        ];

        if (
            empty($thread->contactid) &&
            $contactid !== null
        ) {
            $record->contactid = $contactid;
        }

        if (
            trim((string)($thread->subject ?? '')) === '' &&
            $subject !== null
        ) {
            $record->subject = $subject;
        }

        if ($incrementmessage) {
            $record->messagecount =
                ((int)$thread->messagecount) + 1;
        }

        if ($unread) {
            $record->unreadcount =
                ((int)$thread->unreadcount) + 1;
        }

        if ($lastmessageid !== null) {
            $record->lastmessageid =
                $lastmessageid;
        }

        $DB->update_record(
            self::TABLE,
            $record
        );
    }

    public function set_contact(
        int $threadid,
        ?int $contactid
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $threadid,
                'contactid' => $contactid,
                'timemodified' => time(),
            ]
        );
    }
}