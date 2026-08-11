<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_premium_content_k10h_test extends advanced_testcase {
    public function test_access_mail_uses_square_product_covers_and_bundle_component_covers(): void {
        $root = dirname(__DIR__, 3);
        $factory = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommercePurchaseMailContextFactory.php'
        );
        $presentation = (string)file_get_contents(
            $root . '/classes/commerce/mail/presentation/CommerceMailPurchasePresentation.php'
        );
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('CommerceProductCoverContext::CHECKOUT', $factory);
        $this->assertStringContainsString('product_cover_by_sku', $factory);
        $this->assertStringContainsString('group_bundle_components', $presentation);
        $this->assertStringContainsString('{{#bundlecomponents}}', $template);
        $this->assertStringContainsString('width="112" height="112"', $template);
        $this->assertStringContainsString('width="76" height="76"', $template);
    }

    public function test_access_actions_use_requested_icons_and_open_new_tab(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        foreach ([
            '{{course_icon_url}}',
            '{{download_icon_url}}',
            '{{mobile_icon_url}}',
        ] as $class) {
            $this->assertStringContainsString($class, $template);
        }
        $this->assertStringContainsString('target="_blank"', $template);
        $this->assertStringContainsString('external-white.png', $renderer);
        $this->assertStringContainsString('target="_blank"', $renderer);
    }

    public function test_personal_offer_uses_portrait_cover_inside_gold_card(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/personal_offer.mustache'
        );

        $this->assertStringContainsString('CommerceProductCoverContext::RESOURCES', $service);
        $this->assertStringContainsString("'coverurl'=>", $service);
        $this->assertStringContainsString('{{#personaloffer.hascover}}', $template);
        $this->assertStringContainsString('width="148"', $template);
        $this->assertStringContainsString('min-height:188px', $template);
    }

    public function test_my_campus_button_requests_external_icon(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePurchaseAccessTemplate.php'
        );
        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );

        $this->assertStringContainsString("return 'external';", $template);
        $this->assertStringContainsString("'buttonicon' =>", $abstract);
    }
}
