<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_preview_k10d_test extends advanced_testcase {
    public function test_personal_offer_premium_button_has_no_brand_blue_core(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('bgcolor="#d7b65a"', $renderer);
        $this->assertStringContainsString('background:#d7b65a', $renderer);
        $this->assertStringContainsString('color:#624817', $renderer);
        $this->assertStringContainsString('bgcolor="#fff7df"', $renderer);
    }

    public function test_mail_preview_uses_crm_shell_and_places_resend_in_toolbar(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/mail/view.php');
        $css = (string)file_get_contents($root . '/styles/commerce_mail_admin.css');

        $this->assertStringContainsString('CrmWorkspaceRenderer::start', $view);
        $this->assertStringContainsString('CrmBreadcrumbRenderer::render', $view);
        $this->assertStringContainsString('CrmPageHeader::render', $view);
        $this->assertStringContainsString('CommerceSectionNavigationRenderer::render', $view);
        $this->assertStringContainsString('commerce-mail-preview-toolbar', $view);
        $this->assertStringContainsString('commerce_mail_resend', $view);
        $this->assertStringContainsString('.commerce-mail-preview-toolbar', $css);
    }

    public function test_validity_labels_do_not_duplicate_from_word(): void {
        $root = dirname(__DIR__, 3);
        $expected = [
            'fr' => "Offre valable",
            'en' => "Offer valid",
            'ru' => "Предложение действует",
        ];

        foreach ($expected as $language => $label) {
            $source = (string)file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            $this->assertStringContainsString(
                "\$string['commerce_mail_personal_offer_validity_label'] = '" . $label . "';",
                $source
            );
        }
    }
}
