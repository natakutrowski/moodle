<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_3_3_reply_draft_routing_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_compose_redirects_reply_drafts_instead_of_throwing(): void {
        $compose = $this->file('admin/inbox/compose.php');

        self::assertStringContainsString(
            "if ((string)\$thread->folder !== 'DRAFTS')",
            $compose
        );
        self::assertStringContainsString(
            'admin_inbox_reply_page()',
            $compose
        );
        self::assertStringContainsString(
            "'mode' => 'reply'",
            $compose
        );
    }

    public function test_thread_resume_routes_reply_draft_to_reply_page(): void {
        $thread = $this->file('admin/inbox/thread.php');
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        foreach ([$thread, $renderer] as $content) {
            self::assertStringContainsString(
                'admin_inbox_compose_page()',
                $content
            );
            self::assertStringContainsString(
                'admin_inbox_reply_page()',
                $content
            );
            self::assertStringContainsString(
                "'mode' => 'reply'",
                $content
            );
        }
    }

    public function test_thread_card_only_treats_drafts_folder_as_compose_draft(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            '$iscomposedraft = strtoupper(',
            $renderer
        );
        self::assertStringNotContainsString(
            '$threadurl = $isdraft',
            $renderer
        );
        self::assertStringContainsString(
            '$threadurl = $iscomposedraft',
            $renderer
        );
    }
}
