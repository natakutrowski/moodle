<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_commercial_pricing_surfaces_j67b2_test extends \advanced_testcase {
    public function test_all_customer_surfaces_use_commercial_breakdown(): void {
        global $CFG;
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $cart = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $this->assertStringContainsString('commerce-storefront-price', $storefront);
        $this->assertStringContainsString('commerce-cart-price', $cart);
        $this->assertStringContainsString('cartpricefinalformatted', $checkout);
        $this->assertStringContainsString('cartpricecompareformatted', $checkout);
    }

    public function test_owned_products_hide_prices_and_cart_link(): void {
        global $CFG;
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $panel = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache');
        $this->assertStringContainsString('{{^owned}}', $card);
        $this->assertStringContainsString('fa-graduation-cap', $card);
        $this->assertStringContainsString('{{^owned}}', $panel);
        $this->assertStringContainsString('href="{{carturl}}"', $panel);
        $this->assertStringContainsString('fa-graduation-cap', $panel);
    }

    public function test_upgrade_can_stack_promotion_and_trial(): void {
        global $CFG;
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/pricing/CommerceStorefrontCommercialPricingPresenter.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('commercialpromotionpercent', $presenter);
        $this->assertStringContainsString('commercialtrialpercent', $presenter);
        $this->assertStringContainsString('{{commercialtrialpercent}}', $template);
        $this->assertStringContainsString('{{commercialpromotionpercent}}%', $template);
    }
}
