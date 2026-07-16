<?php

namespace local_subscriptions\crm\work\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads Inbox objects required by Work Management.
 */
final class InboxWorkItemRepository {

    public function get_active_thread(
        int $threadid
    ): \stdClass {
        global $DB;

        if ($threadid <= 0) {
            throw new \InvalidArgumentException(
                'Inbox thread ID must be greater than zero.'
            );
        }

        return $DB->get_record(
            'local_subscriptions_inbox_thread',
            [
                'id' => $threadid,
                'locallydeleted' => 0,
            ],
            'id,contactid',
            MUST_EXIST
        );
    }
}