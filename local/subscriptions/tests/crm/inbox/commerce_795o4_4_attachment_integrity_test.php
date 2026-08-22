<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o4_4_attachment_integrity_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_phantom_local_attachments_are_cleaned_before_apply(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyAttachmentService.php'
        );

        self::assertStringContainsString(
            'cleanup_unstored_local_attachments(',
            $service
        );

        self::assertStringContainsString(
            "str_starts_with(",
            $service
        );

        self::assertStringContainsString(
            "'local-'",
            $service
        );
    }

    public function test_send_cannot_silently_drop_unavailable_attachment(): void {
        $attachmentservice = $this->file(
            'classes/crm/inbox/services/InboxReplyAttachmentService.php'
        );
        $replyservice = $this->file(
            'classes/crm/inbox/services/InboxReplyService.php'
        );

        self::assertStringContainsString(
            'assert_ready_for_send(',
            $attachmentservice
        );

        self::assertStringContainsString(
            'crm_inbox_attachment_not_ready_o44',
            $attachmentservice
        );

        self::assertStringContainsString(
            '->assert_ready_for_send(',
            $replyservice
        );
    }
}
