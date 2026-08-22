<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o7_drafts_autosave_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_autosave_endpoint_and_service_exist(): void {
        $endpoint = $this->file(
            'admin/inbox/autosave.php'
        );

        $service = $this->file(
            'classes/crm/inbox/services/InboxDraftAutosaveService.php'
        );

        self::assertStringContainsString(
            "define('AJAX_SCRIPT', true)",
            $endpoint
        );

        self::assertStringContainsString(
            'InboxDraftAutosaveService',
            $endpoint
        );

        self::assertStringContainsString(
            "'DRAFTS'",
            $service
        );
    }

    public function test_compose_and_reply_enable_autosave(): void {
        foreach (
            [
                'admin/inbox/compose.php',
                'admin/inbox/reply.php',
            ]
            as $relative
        ) {
            $source = $this->file(
                $relative
            );

            self::assertStringContainsString(
                "'data-inbox-autosave-form' => '1'",
                $source
            );

            self::assertStringContainsString(
                'admin_inbox_autosave_page',
                $source
            );
        }
    }

    public function test_amd_has_autosave_and_unsaved_changes_guard(): void {
        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'runAutosave',
            $js
        );

        self::assertStringContainsString(
            "'beforeunload'",
            $js
        );

        self::assertStringContainsString(
            'hasPendingFiles',
            $js
        );

        self::assertStringContainsString(
            'history.replaceState',
            $js
        );
    }

    public function test_drafts_page_can_resume_saved_compose_thread(): void {
        $drafts = $this->file(
            'admin/inbox/drafts.php'
        );

        self::assertStringContainsString(
            'get_compose_drafts(',
            $drafts
        );

        self::assertStringContainsString(
            'admin_inbox_compose_page()',
            $drafts
        );

        self::assertStringContainsString(
            'threadid',
            $drafts
        );
    }

    public function test_manual_reply_draft_persists_envelope(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyService.php'
        );

        self::assertStringContainsString(
            'save_envelope(',
            $service
        );

        self::assertStringContainsString(
            '$this->recipient_service()',
            $service
        );
    }
}
