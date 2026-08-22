<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o18_1_preview_marks_read_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_preview_uses_canonical_mark_read_service(): void {
        $preview = $this->file(
            'ajax/inbox_thread_preview.php'
        );

        self::assertStringContainsString(
            'new InboxThreadActionService(',
            $preview
        );
        self::assertStringContainsString(
            '$actions->mark_read(',
            $preview
        );
        self::assertMatchesRegularExpression(
            '/\$actions->mark_read\(\s*\$threadid,\s*true\s*\);/s',
            $preview
        );
        self::assertStringContainsString(
            'new OvhImapConnector(',
            $preview
        );
        self::assertStringContainsString(
            "'markedread' =>",
            $preview
        );
        self::assertStringContainsString(
            "'unreadcount' =>",
            $preview
        );
    }

    public function test_preview_only_auto_reads_for_manage_inbox_users(): void {
        $preview = $this->file(
            'ajax/inbox_thread_preview.php'
        );

        self::assertMatchesRegularExpression(
            '/\$canmanage\s*&&\s*\(int\)\(\$thread->unreadcount\s*\?\?\s*0\)\s*>\s*0/s',
            $preview
        );
    }

    public function test_thread_cards_expose_unread_state_for_live_update(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            "'data-unread-count' =>",
            $renderer
        );
        self::assertStringContainsString(
            'crm-inbox-thread-card-unread',
            $renderer
        );
        self::assertStringContainsString(
            'crm-inbox-unread-badge',
            $renderer
        );
    }

    public function test_internal_inbox_counter_has_stable_dom_hook(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxSectionNavigationRenderer.php'
        );

        self::assertStringContainsString(
            "'data-inbox-nav-count' => \$key",
            $renderer
        );
    }

    public function test_amd_updates_card_and_unread_counters_after_preview(): void {
        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'applyPreviewReadState',
            $amd
        );
        self::assertStringContainsString(
            "payload.markedread !== true",
            $amd
        );
        self::assertStringContainsString(
            "'crm-inbox-thread-card-unread'",
            $amd
        );
        self::assertStringContainsString(
            'unreadBadge.remove();',
            $amd
        );
        self::assertStringContainsString(
            'SELECTORS.inboxNavUnreadCount',
            $amd
        );
        self::assertStringContainsString(
            'SELECTORS.crmNavigationUnreadBadge',
            $amd
        );
        self::assertMatchesRegularExpression(
            '/applyPreviewReadState\(\s*threadId,\s*payload\s*\);/s',
            $amd
        );
    }
}