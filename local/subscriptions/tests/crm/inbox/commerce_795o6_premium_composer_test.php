<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\services\InboxRecipientService;

final class commerce_795o6_premium_composer_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_recipient_service_deduplicates_across_to_cc_bcc(): void {
        $service = new InboxRecipientService();

        $result = $service->normalize(
            'a@example.test; b@example.test',
            'b@example.test, c@example.test',
            'c@example.test d@example.test'
        );

        self::assertSame(
            [
                'a@example.test',
                'b@example.test',
            ],
            $result['to']
        );

        self::assertSame(
            ['c@example.test'],
            $result['cc']
        );

        self::assertSame(
            ['d@example.test'],
            $result['bcc']
        );
    }

    public function test_compose_page_exists_and_supports_full_recipients(): void {
        $compose = $this->file(
            'admin/inbox/compose.php'
        );

        self::assertStringContainsString(
            'InboxRecipientPickerRenderer::render(',
            $compose
        );

        self::assertStringContainsString(
            "    'to',",
            $compose
        );

        self::assertStringContainsString(
            "        'cc',",
            $compose
        );

        self::assertStringContainsString(
            "        'bcc',",
            $compose
        );

        self::assertStringContainsString(
            'InboxComposeService',
            $compose
        );
    }

    public function test_thread_header_supports_reply_all_and_forward(): void {
        $thread = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringContainsString(
            "'mode' => 'replyall'",
            $thread
        );

        self::assertStringContainsString(
            "'mode' => 'forward'",
            $thread
        );
    }

    public function test_rich_editor_uses_moodle_preferred_editor(): void {
        $reply = $this->file(
            'admin/inbox/reply.php'
        );

        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'editors_get_preferred_editor(FORMAT_HTML)',
            $reply
        );

        self::assertStringContainsString(
            '->use_editor(',
            $reply
        );

        self::assertStringContainsString(
            'tinyEditorFor',
            $js
        );
    }

    public function test_sent_participants_are_persisted(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyService.php'
        );

        self::assertStringContainsString(
            'persist_outbound_participants(',
            $service
        );

        self::assertStringContainsString(
            "'from' => [\$from]",
            $service
        );

        self::assertStringContainsString(
            "new InboxParticipantData(",
            $service
        );
    }
}
