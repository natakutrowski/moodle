<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_k10h2_polish_test extends advanced_testcase {
    public function test_product_titles_strip_html_breaks_and_tags(): void {
        $root = dirname(__DIR__, 3);
        $helper = (string)file_get_contents(
            $root . '/classes/commerce/catalog/presentation/CommerceProductDisplayText.php'
        );
        $readrepo = (string)file_get_contents(
            $root . '/classes/commerce/catalog/readmodel/CommerceCatalogReadRepository.php'
        );

        $this->assertStringContainsString("preg_replace('/<\\s*br", $helper);
        $this->assertStringContainsString('strip_tags', $helper);
        $this->assertStringContainsString('CommerceProductDisplayText::title', $readrepo);
    }

    public function test_order_result_preserves_shared_customer_facing_label(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/order_result.php');

        $this->assertStringContainsString('CommerceProductDisplayText::title($item->label)', $page);
        $this->assertStringContainsString('CommerceStorefrontRepository', $page);
        $this->assertStringNotContainsString('$itemcatalogproduct?->get_name()', $page);
    }

    public function test_mobile_preview_forces_email_shell_to_viewport_width(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailPreviewRenderer.php'
        );
        $css = (string)file_get_contents($root . '/styles/commerce_mail_admin.css');

        $this->assertStringContainsString('inject_mobile_preview_css', $renderer);
        $this->assertStringContainsString('overflow-x:hidden!important', $renderer);
        $this->assertStringContainsString('table.ls-shell', $renderer);
        $this->assertStringContainsString('414px', $css);
    }
}
