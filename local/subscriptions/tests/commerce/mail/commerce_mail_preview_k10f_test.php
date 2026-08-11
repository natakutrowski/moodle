<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_preview_k10f_test extends advanced_testcase {
    public function test_preview_supports_brand_and_fallback_font_modes(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailPreviewRenderer.php'
        );

        $this->assertStringContainsString("FONT_BRAND = 'brand'", $renderer);
        $this->assertStringContainsString("FONT_FALLBACK = 'fallback'", $renderer);
        $this->assertStringContainsString('fonts.googleapis.com', $renderer);
        $this->assertStringContainsString(
            'font-family:Arial,Helvetica,sans-serif!important',
            $renderer
        );
        $this->assertStringContainsString(
            'font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif!important',
            $renderer
        );
    }

    public function test_mail_view_exposes_font_simulator_for_visual_previews(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/mail/view.php');
        $css = (string)file_get_contents($root . '/styles/commerce_mail_admin.css');

        $this->assertStringContainsString('normalise_font', $view);
        $this->assertStringContainsString('render_font_navigation', $view);
        $this->assertStringContainsString('commerce-mail-preview-toolbar__font', $view);
        $this->assertStringContainsString('.commerce-mail-preview-font', $css);
        $this->assertStringContainsString('.commerce-mail-preview-font__link.is-active', $css);
    }

    public function test_font_mode_is_passed_to_iframe_renderer(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/mail/view.php');

        $this->assertStringContainsString('$previewfont', $view);
        $this->assertStringContainsString(
            "CommerceMailPreviewRenderer::render(
    \$preview['html'],
    \$preview['text'],
    \$previewview,
    \$previewfont",
            $view
        );
    }
}
