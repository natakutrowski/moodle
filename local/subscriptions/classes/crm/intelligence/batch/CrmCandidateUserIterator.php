<?php

namespace local_subscriptions\crm\intelligence\batch;

defined('MOODLE_INTERNAL') || die();

final class CrmCandidateUserIterator {

    public function iterate(int $batchsize = 200): \Generator {
        global $DB;

        $lastid = 2;
        $recent = time() - (60 * DAYSECS);

        while (true) {
            $users = $DB->get_records_sql("
                SELECT u.*
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.id > :lastid
                   AND (
                        u.lastaccess >= :recent
                        OR EXISTS (
                            SELECT 1
                              FROM {user_subscription} us
                             WHERE us.userid = u.id
                        )
                   )
              ORDER BY u.id ASC
            ", [
                'lastid' => $lastid,
                'recent' => $recent,
            ], 0, $batchsize);

            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                $lastid = (int)$user->id;
                yield $user;
            }
        }
    }
}