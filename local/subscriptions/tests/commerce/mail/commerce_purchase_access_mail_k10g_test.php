<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_purchase_access_mail_k10g_test extends advanced_testcase {
    public function test_access_mail_is_content_focused(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/purchase_access.mustache'
        );
        $accessitem = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );
        $class = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePurchaseAccessTemplate.php'
        );

        $this->assertStringContainsString(
            'commerce/mail/components/access_item',
            $template
        );
        $this->assertStringNotContainsString('links.hascourses', $template);
        $this->assertStringNotContainsString('links.hasresources', $template);
        $this->assertStringNotContainsString('links.haspurchases', $template);
        $this->assertStringContainsString('commerce_mail_access_my_campus', $class);
        $this->assertStringContainsString("/mon-campus", $class);
        $this->assertStringContainsString('#f72585', $accessitem);
    }

    public function test_digital_access_builds_desktop_and_mobile_links(): void {
        $root = dirname(__DIR__, 3);
        $factory = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommercePurchaseMailContextFactory.php'
        );
        $presentation = (string)file_get_contents(
            $root . '/classes/commerce/mail/presentation/CommerceMailPurchasePresentation.php'
        );
        $accessitem = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString("'version' => 'desktop'", $factory);
        $this->assertStringContainsString("'version' => 'mobile'", $factory);
        $this->assertStringContainsString("'isdesktop'", $presentation);
        $this->assertStringContainsString("'ismobile'", $presentation);
        $this->assertStringContainsString('download_desktop_label', $accessitem);
        $this->assertStringContainsString('download_mobile_label', $accessitem);
    }

    public function test_internal_action_keys_are_not_customer_labels(): void {
        $root = dirname(__DIR__, 3);
        $factory = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommercePurchaseMailContextFactory.php'
        );

        $this->assertStringContainsString(
            "['open_course', 'download_file', 'open_access']",
            $factory
        );
        $this->assertStringContainsString(
            "translated_product_name_by_sku",
            $factory
        );
    }

    public function test_bundle_accesses_are_visually_separated(): void {
        $root = dirname(__DIR__, 3);
        $accessitem = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('{{#isbundle}}', $accessitem);
        $this->assertStringContainsString('bundle_contents_label', $accessitem);
        $this->assertStringContainsString('border-top:1px solid #eee8f1', $accessitem);
    }

    public function test_standard_mail_cta_uses_campusfr_pink_premium_style(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('bgcolor="#f72585"', $renderer);
        $this->assertStringContainsString('box-shadow:0 9px 22px rgba(247,37,133,.20)', $renderer);
    }
}
