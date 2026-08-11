<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;

final class commerce_mail_admin_preview_test extends \advanced_testcase {

    public function test_preview_modes_are_rendered_and_invalid_mode_falls_back_to_desktop(): void {
        $this->resetAfterTest();
        $url = new \moodle_url('/local/subscriptions/admin/commerce/mail/view.php', ['id' => 12]);

        $navigation = CommerceMailPreviewRenderer::render_navigation($url, CommerceMailPreviewRenderer::MOBILE);
        $this->assertStringContainsString('view=desktop', $navigation);
        $this->assertStringContainsString('view=mobile', $navigation);
        $this->assertStringContainsString('view=text', $navigation);
        $this->assertStringContainsString('view=source', $navigation);
        $this->assertStringContainsString('is-active', $navigation);

        $desktop = CommerceMailPreviewRenderer::render('<p>CampusFR</p>', 'CampusFR', 'invalid');
        $this->assertStringContainsString('commerce-mail-preview-device is-desktop', $desktop);
        $this->assertStringContainsString('srcdoc=', $desktop);
    }

    public function test_source_and_text_views_escape_their_content(): void {
        $this->resetAfterTest();

        $source = CommerceMailPreviewRenderer::render('<script>alert(1)</script>', '', CommerceMailPreviewRenderer::SOURCE);
        $this->assertStringNotContainsString('<script>', $source);
        $this->assertStringContainsString('&lt;script&gt;', $source);

        $text = CommerceMailPreviewRenderer::render('', '<strong>Plain</strong>', CommerceMailPreviewRenderer::TEXT);
        $this->assertStringNotContainsString('<strong>Plain</strong>', $text);
        $this->assertStringContainsString('&lt;strong&gt;Plain&lt;/strong&gt;', $text);
    }
}
