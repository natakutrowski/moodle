<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_order_details_ux_test extends advanced_testcase {
    public function test_order_details_exposes_personal_offer_traceability(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/order_details.php');
        $template = (string)file_get_contents($root . '/templates/order_details/page.mustache');

        $this->assertStringContainsString('personal_offer_uuid', $page);
        $this->assertStringContainsString('MoodleCommercePersonalOfferRepository', $page);
        $this->assertStringContainsString('personalofferlabel', $page);
        $this->assertStringContainsString('personalofferid', $page);
        $this->assertStringContainsString('personalofferurl', $page);

        $this->assertStringContainsString('promotion.ispersonaloffer', $template);
        $this->assertStringContainsString('promotion.personalofferlabel', $template);
        $this->assertStringContainsString('promotion.personalofferid', $template);
        $this->assertStringContainsString('promotion.personalofferurl', $template);
    }

    public function test_personal_offer_detail_uses_translated_status_and_purchase_links(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents(
            $root . '/admin/commerce/personal-offers/view.php'
        );

        $this->assertStringContainsString('commerce_personal_offer_status_redeemed', $view);
        $this->assertStringContainsString("'badge bg-success'", $view);
        $this->assertStringContainsString('admin/commerce/purchases/view.php', $view);
        $this->assertStringContainsString("'text-muted small'", $view);
    }

    public function test_checkout_does_not_render_alternative_login_button(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents($root . '/templates/checkout/page.mustache');

        $this->assertStringNotContainsString('{{embeddedloginalternativelabel}}', $template);
    }
}
