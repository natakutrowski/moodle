<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_preview_k10e_test extends advanced_testcase {
    public function test_email_font_stack_prefers_nunito_with_safe_fallbacks(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString(
            "font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;",
            $renderer
        );
    }

    public function test_personal_offer_cta_uses_soft_ivory_gold_palette(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('bgcolor="#fff7df"', $renderer);
        $this->assertStringContainsString('bgcolor="#d7b65a"', $renderer);
        $this->assertStringContainsString('color:#624817', $renderer);
    }

    public function test_preview_toolbar_keeps_resend_beside_modes_on_desktop(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/mail/view.php');
        $css = (string)file_get_contents($root . '/styles/commerce_mail_admin.css');

        $this->assertStringContainsString(
            'commerce-mail-preview-toolbar__navigation',
            $view
        );
        $this->assertStringContainsString(
            'commerce-mail-preview-toolbar__actions',
            $view
        );
        $this->assertStringContainsString(
            '@media (min-width: 768px)',
            $css
        );
        $this->assertStringContainsString(
            'flex-wrap: nowrap',
            $css
        );
        $this->assertStringContainsString(
            'white-space: nowrap',
            $css
        );
    }
}
