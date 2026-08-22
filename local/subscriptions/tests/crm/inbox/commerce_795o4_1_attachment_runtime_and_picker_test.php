<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o4_1_attachment_runtime_and_picker_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_attachment_service_does_not_use_nonexistent_param_mimetype(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyAttachmentService.php'
        );

        $tokens = token_get_all($service);
        $executable = '';

        foreach ($tokens as $token) {
            if (
                is_array($token)
                && in_array(
                    $token[0],
                    [T_COMMENT, T_DOC_COMMENT],
                    true
                )
            ) {
                continue;
            }

            $executable .= is_array($token)
                ? $token[1]
                : $token;
        }

        self::assertStringNotContainsString(
            'PARAM_MIMETYPE',
            $executable
        );

        self::assertStringContainsString(
            'normalize_mimetype',
            $service
        );

        self::assertStringContainsString(
            "'application/octet-stream'",
            $service
        );
    }

    public function test_reply_file_picker_supports_additive_selection_ui(): void {
        $reply = $this->file(
            'admin/inbox/reply.php'
        );

        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            "'multiple' => 'multiple'",
            $reply
        );

        self::assertStringContainsString(
            "'data-inbox-attachment-input' => '1'",
            $reply
        );

        self::assertStringContainsString(
            'attachmentQueues = new WeakMap()',
            $js
        );

        self::assertStringContainsString(
            'new window.DataTransfer()',
            $js
        );

        self::assertStringContainsString(
            'handleAttachmentSelection',
            $js
        );
    }
}
