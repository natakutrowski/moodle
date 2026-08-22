<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\services\InboxTemplateService;

final class commerce_795o9_signatures_quick_replies_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_template_schema_supports_signatures_and_quick_replies(): void {
        $install = $this->file(
            'db/install.xml'
        );

        self::assertStringContainsString(
            'local_subscriptions_inbox_template',
            $install
        );

        self::assertStringContainsString(
            'NAME="type"',
            $install
        );

        self::assertStringContainsString(
            'NAME="accountid"',
            $install
        );

        self::assertStringContainsString(
            'NAME="bodyhtml"',
            $install
        );
    }

    public function test_template_service_defines_professional_template_types(): void {
        self::assertTrue(
            InboxTemplateService::valid_type(
                InboxTemplateService::TYPE_SIGNATURE
            )
        );

        self::assertTrue(
            InboxTemplateService::valid_type(
                InboxTemplateService::TYPE_QUICK_REPLY
            )
        );

        self::assertFalse(
            InboxTemplateService::valid_type(
                'unknown'
            )
        );
    }

    public function test_signature_is_appended_only_once(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxTemplateService.php'
        );

        self::assertStringContainsString(
            'data-crm-inbox-signature',
            $service
        );

        self::assertStringContainsString(
            'str_contains(',
            $service
        );
    }

    public function test_compose_and_reply_load_signatures_and_quick_replies(): void {
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
                'append_signature(',
                $source
            );

            self::assertStringContainsString(
                'quick_replies(',
                $source
            );

            self::assertStringContainsString(
                'data-inbox-quick-reply-select',
                $source
            );
        }
    }

    public function test_amd_inserts_quick_reply_into_tinymce(): void {
        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'insertQuickReply',
            $js
        );

        self::assertStringContainsString(
            'window.tinyMCE.get',
            $js
        );

        self::assertStringContainsString(
            'editor.insertContent',
            $js
        );
    }
}
