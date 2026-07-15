<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxDiagnosticsRepository {

    public function table_exists(
        string $tablename
    ): bool {
        global $DB;

        return $DB->get_manager()->table_exists(
            new \xmldb_table($tablename)
        );
    }

    public function count(
        string $tablename,
        array $conditions = []
    ): int {
        global $DB;

        if (!$this->table_exists($tablename)) {
            return 0;
        }

        return (int)$DB->count_records(
            $tablename,
            $conditions
        );
    }

    public function latest_sync_log(
        int $accountid
    ): ?object {
        global $DB;

        $records = $DB->get_records(
            'local_subscriptions_inbox_sync_log',
            ['accountid' => $accountid],
            'startedat DESC, id DESC',
            '*',
            0,
            1
        );

        if (!$records) {
            return null;
        }

        return reset($records) ?: null;
    }

    public function failed_attachment_count(): int {
        return $this->count(
            'local_subscriptions_inbox_attachment',
            ['downloadstatus' => 'failed']
        );
    }

    public function pending_attachment_count(): int {
        return $this->count(
            'local_subscriptions_inbox_attachment',
            ['downloadstatus' => 'pending']
        );
    }

    public function unmatched_contact_count(): int {
        return $this->count(
            'local_subscriptions_inbox_contact',
            ['matchstatus' => 'unmatched']
        );
    }

    public function ambiguous_contact_count(): int {
        return $this->count(
            'local_subscriptions_inbox_contact',
            ['matchstatus' => 'ambiguous']
        );
    }
}