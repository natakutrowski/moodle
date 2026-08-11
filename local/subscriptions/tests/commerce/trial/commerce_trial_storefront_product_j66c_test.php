<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6C Storefront Trial price hierarchy contract. */
final class commerce_trial_storefront_product_j66c_test
        extends \advanced_testcase {

    public function test_trial_price_replaces_standard_price_block(): void {
        global $CFG;
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache');
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $this->assertStringContainsString('local_subscriptions/storefront/product_price', $page);
        $this->assertStringContainsString('commerce-storefront-price--trial', $price);
        $this->assertStringContainsString('commerce-storefront-price--standard', $price);
    }

    public function test_controller_builds_cumulative_price_stages(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/storefront_product.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'hasproductpromotionbeforetrial',
            $source
        );
        $this->assertStringContainsString(
            'productpromotionformatted',
            $source
        );
        $this->assertStringContainsString(
            'trialbaseformatted',
            $source
        );
        $this->assertStringContainsString(
            'trialformatted',
            $source
        );
        $this->assertStringContainsString(
            'commerce_trial_storefront_deadline',
            $source
        );
    }
}
