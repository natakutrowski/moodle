<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_access_mail_k10h1_test extends advanced_testcase {
    public function test_bundle_cover_falls_back_to_purchase_item_reference(): void {
        $root = dirname(__DIR__, 3);
        $factory = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommercePurchaseMailContextFactory.php'
        );

        $this->assertStringContainsString(
            "\$item->metadata['productsku'] ?? \$item->reference",
            $factory
        );
        $this->assertStringContainsString(
            'CommerceProductCoverContext::CHECKOUT',
            $factory
        );
    }

    public function test_email_icons_are_hosted_png_assets_not_inline_svg(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );
        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringNotContainsString('<svg class="fa-solid fa-graduation-cap"', $template);
        $this->assertStringNotContainsString('<svg class="fa-solid fa-download"', $template);
        $this->assertStringNotContainsString('<svg class="fa-solid fa-mobile-screen-button"', $template);

        $this->assertStringContainsString('graduation-cap-white.png', $abstract);
        $this->assertStringContainsString('download-white.png', $abstract);
        $this->assertStringContainsString('mobile-pink.png', $abstract);
        $this->assertStringContainsString('external-white.png', $renderer);
    }

    public function test_access_thumbnails_are_larger(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('width="112" height="112"', $template);
        $this->assertStringContainsString('width="76" height="76"', $template);
    }

    public function test_access_links_still_open_new_target(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('target="_blank"', $template);
        $this->assertStringContainsString('rel="noopener noreferrer"', $template);
    }
}
