<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_template_preview_controls_test extends advanced_testcase {

    public function test_template_preview_has_edit_font_and_language_controls(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/admin/commerce/mail/templates/preview.php'
        );

        $this->assertStringContainsString(
            "/admin/commerce/mail/templates/edit.php",
            $source
        );
        $this->assertStringContainsString(
            'CommerceMailPreviewRenderer::normalise_font',
            $source
        );
        $this->assertStringContainsString(
            'CommerceMailPreviewRenderer::render_font_navigation',
            $source
        );
        $this->assertStringContainsString(
            "'language',",
            $source
        );
        $this->assertStringContainsString(
            "name' => 'view'",
            $source
        );
        $this->assertStringContainsString(
            "name' => 'font'",
            $source
        );
    }

    public function test_template_preview_passes_selected_font_to_renderer(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/admin/commerce/mail/templates/preview.php'
        );

        $this->assertStringContainsString(
            '$previewfont',
            $source
        );
        $this->assertStringContainsString(
            '$message->get_text(),',
            $source
        );
    }
}
