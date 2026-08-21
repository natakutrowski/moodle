<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n122_inbox_preview_badges_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_thread_cards_use_compact_badge_stack(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-thread-badges',
            $renderer
        );
        self::assertStringContainsString(
            'crm_inbox_unread_count_compact',
            $renderer
        );
    }

    public function test_remote_images_are_removed_and_reported_once(): void {
        $sanitizer = $this->file(
            'classes/crm/inbox/rendering/InboxHtmlSanitizer.php'
        );

        self::assertStringContainsString(
            '$blockedremoteimages',
            $sanitizer
        );
        self::assertStringContainsString(
            'removeChild($image)',
            $sanitizer
        );
        self::assertStringContainsString(
            'crm_inbox_remote_images_blocked_summary',
            $sanitizer
        );
        self::assertStringNotContainsString(
            "createElement(\n                'span'",
            $sanitizer
        );
    }
}
