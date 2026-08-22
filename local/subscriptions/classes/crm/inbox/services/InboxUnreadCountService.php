<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the global CRM Inbox unread-message count.
 *
 * O2 guarantees that thread.unreadcount only represents unread inbound
 * messages, so the navbar can use the denormalized thread counter without
 * joining/parsing message rows on every CRM page request.
 */
final class InboxUnreadCountService {

    private ?int $requestcache = null;

    public function count(): int {
        global $DB;

        if ($this->requestcache !== null) {
            return $this->requestcache;
        }

        $sql = "
            SELECT COALESCE(SUM(thread.unreadcount), 0)
              FROM {local_subscriptions_inbox_thread} thread
              JOIN {local_subscriptions_inbox_account} account
                ON account.id = thread.accountid
             WHERE thread.locallydeleted = 0
               AND account.enabled = 1
               AND thread.unreadcount > 0
        ";

        $this->requestcache = max(
            0,
            (int)$DB->get_field_sql($sql)
        );

        return $this->requestcache;
    }
}
