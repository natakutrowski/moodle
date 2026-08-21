<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n125a_thread_chronology_reply_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_thread_messages_are_sorted_newest_first(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        self::assertStringContainsString(
            'usort(',
            $renderer
        );

        self::assertStringContainsString(
            '$rightdate <=> $leftdate',
            $renderer
        );

        self::assertStringContainsString(
            'private static function message_timestamp(',
            $renderer
        );
    }

    public function test_thread_header_exposes_reply_action_for_managers(): void {
        $page = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringContainsString(
            'crm-inbox-thread-header-reply',
            $page
        );

        self::assertStringContainsString(
            'admin_inbox_reply_page()',
            $page
        );

        self::assertStringContainsString(
            'Capabilities::MANAGE_INBOX',
            $page
        );

        self::assertStringContainsString(
            'CrmPageHeader::render(',
            $page
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
