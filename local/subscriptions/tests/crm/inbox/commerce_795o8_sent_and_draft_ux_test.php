<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o8_sent_and_draft_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_draft_message_does_not_render_sent_direction_badge(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        $draftpos = strpos(
            $renderer,
            "if ($" . "message->status === 'draft')"
        );

        $directionpos = strpos(
            $renderer,
            "'crm_inbox_direction_'"
        );

        self::assertNotFalse($draftpos);
        self::assertNotFalse($directionpos);
        self::assertLessThan(
            $directionpos,
            $draftpos
        );

        self::assertStringContainsString(
            'thread_has_draft(',
            $renderer
        );
    }

    public function test_draft_preview_and_thread_resume_compose(): void {
        $preview = $this->file(
            'classes/crm/inbox/rendering/InboxThreadPreviewRenderer.php'
        );

        $thread = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringContainsString(
            'admin_inbox_compose_page()',
            $preview
        );

        self::assertStringContainsString(
            'crm_inbox_resume_draft_o7',
            $preview
        );

        self::assertStringContainsString(
            '$threadhasdraft',
            $thread
        );

        self::assertStringContainsString(
            'admin_inbox_compose_page()',
            $thread
        );
    }

    public function test_smtp_uses_exact_sent_mime_for_imap_append(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            '$mailer->preSend()',
            $smtp
        );

        self::assertStringContainsString(
            '$mailer->getSentMIMEMessage()',
            $smtp
        );

        self::assertStringContainsString(
            '$mailer->postSend()',
            $smtp
        );

        self::assertStringContainsString(
            'imap_append(',
            $smtp
        );

        self::assertStringContainsString(
            "'\\\\Seen'",
            $smtp
        );
    }

    public function test_sent_folder_is_discovered_instead_of_hardcoded(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            'InboxFolderResolver',
            $smtp
        );

        self::assertStringContainsString(
            "['sent']",
            $smtp
        );

        self::assertStringContainsString(
            'imap_getmailboxes(',
            $smtp
        );
    }

    public function test_sent_copy_failure_does_not_turn_smtp_success_into_send_failure(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            'The SMTP delivery has already succeeded',
            $smtp
        );

        self::assertStringContainsString(
            '$sentcopyerror',
            $smtp
        );

        $drafts = $this->file(
            'classes/crm/inbox/repositories/InboxDraftRepository.php'
        );

        self::assertStringContainsString(
            'record_sent_copy_result(',
            $drafts
        );
    }

    public function test_sync_deduplicates_sent_copy_by_message_id(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxMessageRepository.php'
        );

        self::assertStringContainsString(
            "'providermessageid' =>",
            $repository
        );

        self::assertStringContainsString(
            'find_existing(',
            $repository
        );

        self::assertStringContainsString(
            "'message-id|' . $" . "messageid",
            $repository
        );
    }
}
