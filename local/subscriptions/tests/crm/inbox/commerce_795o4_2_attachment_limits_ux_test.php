<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o4_2_attachment_limits_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_mimetype_regex_uses_safe_delimiter(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyAttachmentService.php'
        );

        self::assertStringContainsString(
            "'~^[a-z0-9]",
            $service
        );

        self::assertStringNotContainsString(
            "'#^[a-z0-9][a-z0-9!#$",
            $service
        );
    }

    public function test_reply_input_exposes_attachment_limits_to_javascript(): void {
        $reply = $this->file(
            'admin/inbox/reply.php'
        );

        self::assertStringContainsString(
            "'data-max-file-size'",
            $reply
        );

        self::assertStringContainsString(
            "'data-max-total-size'",
            $reply
        );

        self::assertStringContainsString(
            "'data-existing-total-size'",
            $reply
        );
    }

    public function test_picker_blocks_oversized_file_before_submit(): void {
        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'file.size > limits.maxFileSize',
            $js
        );

        self::assertStringContainsString(
            'total > limits.maxTotalSize',
            $js
        );

        self::assertStringContainsString(
            'renderAttachmentBudget',
            $js
        );

        self::assertStringContainsString(
            'formatBytes(file.size)',
            $js
        );
    }
}
