<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\services\InboxReplyHtmlService;

final class commerce_795o5_1_inline_html_runtime_fix_test
    extends \advanced_testcase {

    public function test_sanitize_preserves_cid_image_and_does_not_emit_xml_pi(): void {
        $service = new InboxReplyHtmlService();

        $html = '<p>Bonjour</p>'
            . '<img src="cid:crm-inline-123@campusfr" alt="Test">'
            . '<p>Fin</p>';

        $clean = $service->sanitize(
            $html
        );

        self::assertStringContainsString(
            'cid:crm-inline-123@campusfr',
            $clean
        );

        self::assertStringContainsString(
            '<img',
            $clean
        );

        self::assertStringNotContainsString(
            '<?xml',
            $clean
        );

        self::assertStringNotContainsString(
            'crm-inline.invalid',
            $clean
        );
    }

    public function test_sanitize_removes_untrusted_non_cid_image(): void {
        $service = new InboxReplyHtmlService();

        $clean = $service->sanitize(
            '<p>A</p>'
            . '<img src="https://example.invalid/tracker.png">'
            . '<p>B</p>'
        );

        self::assertStringNotContainsString(
            'example.invalid',
            $clean
        );

        self::assertStringContainsString(
            '<p>A</p>',
            $clean
        );

        self::assertStringContainsString(
            '<p>B</p>',
            $clean
        );
    }

    public function test_referenced_cids_are_still_detected_after_sanitize(): void {
        $service = new InboxReplyHtmlService();

        $clean = $service->sanitize(
            '<img src="cid:first@campusfr">'
            . '<img src="cid:second@campusfr">'
        );

        self::assertSame(
            [
                'first@campusfr',
                'second@campusfr',
            ],
            $service->referenced_cids(
                $clean
            )
        );
    }
}
