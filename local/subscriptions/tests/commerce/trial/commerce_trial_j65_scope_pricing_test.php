<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.5 Trial scope and customer pricing contract. */
final class commerce_trial_j65_scope_pricing_test extends \advanced_testcase {
    public function test_obsolete_trial_target_settings_are_removed(): void {
        global $CFG;

        $settings = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/settings.php'
        );
        $bridge = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/trial/' .
            'CommerceTrialConversionBridge.php'
        );

        $this->assertIsString($settings);
        $this->assertStringNotContainsString(
            'trial_conversion_product_sku',
            $settings
        );
        $this->assertStringNotContainsString(
            'trial_conversion_plan_id',
            $settings
        );

        $this->assertIsString($bridge);
        $this->assertStringContainsString(
            'CommerceTrialProductEligibilityService',
            $bridge
        );
    }

    public function test_trial_discount_is_line_level_not_a_promo_code(): void {
        global $CFG;

        $calculator = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/cart/service/' .
            'CommerceCartCalculator.php'
        );

        $this->assertIsString($calculator);
        $this->assertStringNotContainsString("'TRIAL-'", $calculator);
        $this->assertStringContainsString(
            '$trialprice->get_total_minor()',
            $calculator
        );
    }

    public function test_storefront_and_catalog_expose_trial_prices(): void {
        global $CFG;
        $panel = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache');
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('local_subscriptions/storefront/product_price', $panel);
        $this->assertStringContainsString('local_subscriptions/storefront/product_price', $card);
        $this->assertStringContainsString('commerce-storefront-price--trial', $price);
    }
}
