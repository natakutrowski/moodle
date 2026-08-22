<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o5_inline_cid_images_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_schema_persists_inline_cid_metadata(): void {
        $install = $this->file(
            'db/install.xml'
        );

        self::assertStringContainsString(
            'NAME="contentid"',
            $install
        );

        self::assertStringContainsString(
            'NAME="isinline"',
            $install
        );
    }

    public function test_smtp_uses_embedded_images_for_inline_attachments(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            'addStringEmbeddedImage(',
            $smtp
        );

        self::assertStringContainsString(
            '$attachment->contentid',
            $smtp
        );
    }

    public function test_reply_ui_supports_select_paste_and_drop_inline_images(): void {
        $reply = $this->file(
            'admin/inbox/reply.php'
        );

        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            "'name' => 'inlineimages[]'",
            $reply
        );

        self::assertStringContainsString(
            'handleInlinePaste',
            $js
        );

        self::assertStringContainsString(
            'handleInlineDrop',
            $js
        );

        self::assertStringContainsString(
            "'cid:' + cid",
            $js
        );
    }

    public function test_incoming_inline_images_are_resolved_from_stored_cid(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        self::assertStringContainsString(
            'resolve_inline_images(',
            $renderer
        );

        self::assertStringContainsString(
            '$attachment->contentid',
            $renderer
        );
    }

    public function test_reply_html_removes_non_cid_images(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReplyHtmlService.php'
        );

        self::assertStringContainsString(
            'crm-inline.invalid',
            $service
        );

        self::assertStringContainsString(
            "'cid:' . \$cid",
            $service
        );

        self::assertStringContainsString(
            'removeChild',
            $service
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
            '<?xml encoding=',
            $executable
        );

        self::assertStringContainsString(
            'crm-inline.invalid',
            $service
        );
    }
}
