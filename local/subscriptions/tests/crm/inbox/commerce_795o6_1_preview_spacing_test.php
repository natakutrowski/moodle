<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\rendering\InboxHtmlSanitizer;

final class commerce_795o6_1_preview_spacing_test
    extends \advanced_testcase {

    public function test_inline_formatting_keeps_spaces_in_crm_preview(): void {
        $sanitizer = new InboxHtmlSanitizer();

        $html = '<strong>Gras</strong> '
            . '<em>italique</em> '
            . '<u>souligne</u>';

        $clean = $sanitizer->sanitize(
            $html,
            true
        );

        self::assertStringContainsString(
            '</strong> <em>',
            $clean
        );

        self::assertStringContainsString(
            '</em> <u>',
            $clean
        );

        self::assertStringNotContainsString(
            'CRM_INBOX_INLINE_SPACE',
            $clean
        );
    }

    public function test_preview_never_emits_xml_processing_instruction(): void {
        $sanitizer = new InboxHtmlSanitizer();

        $clean = $sanitizer->sanitize(
            '<p>Bonjour</p>',
            true
        );

        self::assertStringNotContainsString(
            '<?xml',
            $clean
        );

        self::assertStringNotContainsString(
            'crm-inbox-render-root',
            $clean
        );
    }
}
