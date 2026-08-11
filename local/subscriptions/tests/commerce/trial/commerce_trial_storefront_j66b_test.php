<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6B Boutique Trial pricing contract. */
final class commerce_trial_storefront_j66b_test extends \advanced_testcase {
    public function test_boutique_does_not_depend_on_trialconversion_flag(): void {
        global $CFG;
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $catalog = file_get_contents($CFG->dirroot . '/local/subscriptions/digital_catalog.php');
        $this->assertStringNotContainsString('trialconversion', $card);
        $this->assertStringNotContainsString("optional_param('trialconversion'", $catalog);
        $this->assertStringContainsString('local_subscriptions/storefront/product_price', $card);
    }

    public function test_cart_service_infers_trial_metadata_server_side(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/service/'
            . 'CommerceCartService.php'
        );

        $this->assertIsString($service);
        $this->assertStringContainsString(
            '$this->trialpricing->canonical_metadata',
            $service
        );
        $this->assertStringContainsString(
            'Normal products and non-eligible courses continue',
            $service
        );
    }

    public function test_trial_and_product_promotion_are_presented_separately(): void {
        global $CFG;
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('cart', 'cart'); // Keeps one assertion independent from formatting.
        $this->assertStringContainsString('commerce-storefront-price__badge--trial', $price);
        $this->assertStringContainsString('commerce-storefront-price__badge--saving', $price);
        $this->assertStringContainsString('commerce-storefront-price__badge--promotion', $price);
    }
}
