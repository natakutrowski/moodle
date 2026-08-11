<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B5 pricing consistency and premium presentation contract. */
final class commerce_commercial_pricing_j67b5_test
        extends \advanced_testcase {

    public function test_upgrade_trial_is_not_applied_twice_in_cart(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/service/'
            . 'CommerceCartCalculator.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'if ($istrialconversion && !$isupgrade)',
            $source
        );
        $this->assertStringContainsString(
            'already include',
            $source
        );
    }

    public function test_all_upgrade_surfaces_use_premium_shared_partial(): void {
        global $CFG;
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $cart = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $this->assertStringContainsString('commerce-storefront-price', $storefront);
        $this->assertStringContainsString('commerce-cart-price', $cart);
        $this->assertStringContainsString('cartpricefinalformatted', $checkout);
        $this->assertStringContainsString('cartpricecompareformatted', $checkout);
    }

    public function test_trial_offer_is_full_width_and_uses_initial_promotion(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );
        $catalogue = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/digital_catalog.php'
        );

        $this->assertStringContainsString(
            '.commerce-product-card__trial-offer',
            $styles
        );
        $this->assertStringContainsString(
            'width: 100%',
            $styles
        );
        $this->assertStringContainsString(
            'commerce_pricing_initial_promotion',
            $catalogue
        );
    }
}
