<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminLog;

final class UserNoteService {

    public static function add(int $userid, string $note): void {
        global $DB, $USER;

        $note = trim($note);

        if ($note === '') {
            throw new \moodle_exception('crm_note_required', 'local_subscriptions');
        }

        $DB->insert_record('local_subscriptions_user_note', (object)[
            'userid' => $userid,
            'authorid' => (int)$USER->id,
            'note' => $note,
            'timecreated' => time(),
        ]);

        AdminLog::log('user.note.added', $userid, 'user', $userid);
    }

    public static function get_for_user(int $userid, int $limit = 20): array {
        global $DB;

        return $DB->get_records(
            'local_subscriptions_user_note',
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );
    }
}