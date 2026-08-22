<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o4_attachments_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_reply_request_supports_attachments(): void {
        $dto = $this->file(
            'classes/crm/inbox/dto/InboxReplyRequest.php'
        );

        self::assertStringContainsString(
            'public readonly array $attachments = []',
            $dto
        );
    }

    public function test_smtp_sends_string_attachments(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            '$request->attachments as $attachment',
            $smtp
        );
        self::assertStringContainsString(
            'addStringAttachment(',
            $smtp
        );
    }

    public function test_reply_form_is_multipart_and_accepts_multiple_files(): void {
        $reply = $this->file(
            'admin/inbox/reply.php'
        );

        self::assertStringContainsString(
            "'enctype' => 'multipart/form-data'",
            $reply
        );
        self::assertStringContainsString(
            "'name' => 'attachments[]'",
            $reply
        );
        self::assertStringContainsString(
            "'multiple' => 'multiple'",
            $reply
        );
    }

    public function test_reply_attachment_service_limits_and_moodle_file_storage(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyAttachmentService.php'
        );

        self::assertStringContainsString(
            'MAX_FILES = 10',
            $service
        );
        self::assertStringContainsString(
            'MAX_FILE_SIZE = 10485760',
            $service
        );
        self::assertStringContainsString(
            'MAX_TOTAL_SIZE = 26214400',
            $service
        );
        self::assertStringContainsString(
            'MoodleFileInboxAttachmentStorage',
            $service
        );
    }

    public function test_saved_draft_files_are_used_for_send(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyService.php'
        );

        self::assertStringContainsString(
            '->outbound_attachments(',
            $service
        );
        self::assertStringContainsString(
            '$attachments',
            $service
        );
    }
}
