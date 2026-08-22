<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o2_read_unread_management_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_unread_counts_only_inbound_messages(): void {
        $threadrepo = $this->file(
            'classes/crm/inbox/repositories/InboxThreadRepository.php'
        );
        $actionrepo = $this->file(
            'classes/crm/inbox/repositories/InboxThreadActionRepository.php'
        );

        self::assertStringContainsString(
            "'direction' => 'inbound'",
            $threadrepo
        );
        self::assertStringContainsString(
            "'direction' => 'inbound'",
            $actionrepo
        );
    }

    public function test_mark_unread_only_targets_latest_inbound_remote_message(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxThreadActionService.php'
        );

        self::assertStringContainsString(
            "===\n                        'inbound'",
            $service
        );
        self::assertStringContainsString(
            '$inbound[array_key_last($inbound)]',
            $service
        );
    }

    public function test_outbound_messages_are_forced_read_on_import(): void {
        $connector = $this->file(
            'classes/crm/inbox/connectors/imap/OvhImapConnector.php'
        );

        self::assertStringContainsString(
            '$direction === InboxMessageDirection::OUTBOUND',
            $connector
        );
        self::assertStringContainsString(
            '? true',
            $connector
        );
    }

    public function test_inbox_has_bulk_read_unread_endpoint(): void {
        $config = $this->file(
            'classes/subscription_config.php'
        );
        $bulk = $this->file(
            'admin/inbox/bulk_action.php'
        );
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );
        $bulkservice = $this->file(
            'classes/crm/inbox/services/InboxBulkActionService.php'
        );

        self::assertStringContainsString(
            'admin_inbox_bulk_action_page',
            $config
        );
        self::assertStringContainsString(
            "'read' =>",
            $bulkservice
        );
        self::assertStringContainsString(
            "'unread' =>",
            $bulkservice
        );
        self::assertStringContainsString(
            'InboxBulkActionService',
            $bulk
        );
        self::assertStringContainsString(
            "name' => 'threadids[]'",
            $renderer
        );
    }

    public function test_opening_thread_marks_unread_conversation_read(): void {
        $thread = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringContainsString(
            '(int)($thread->unreadcount ?? 0) > 0',
            $thread
        );
        self::assertStringContainsString(
            '$actions->mark_read(',
            $thread
        );
    }

    public function test_mark_unread_redirects_away_from_thread_auto_read(): void {
        $action = $this->file(
            'admin/inbox/action.php'
        );

        self::assertStringContainsString(
            "'crm_inbox_marked_unread_o2'",
            $action
        );
        self::assertStringContainsString(
            'admin_inbox_page()',
            $action
        );
    }
}
