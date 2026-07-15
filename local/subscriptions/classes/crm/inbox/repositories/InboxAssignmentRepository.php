<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxAssignmentRepository {

    private const TABLE =
        'local_subscriptions_inbox_thread';

    public function assign(
        int $threadid,
        ?int $userid,
        ?int $teamid
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $threadid,
                'assigneduserid' => $userid,
                'assignedteamid' => $teamid,
                'timemodified' => time(),
            ]
        );
    }

    public function unassign(
        int $threadid
    ): void {
        $this->assign(
            $threadid,
            null,
            null
        );
    }
}