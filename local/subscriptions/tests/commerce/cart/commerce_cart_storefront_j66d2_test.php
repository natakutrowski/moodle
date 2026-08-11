<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6D2 cart and Boutique stabilisation contract. */
final class commerce_cart_storefront_j66d2_test extends \advanced_testcase {

    public function test_boutique_explicitly_loads_campus_banner_provider(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$CFG->dirroot . '/local/campus/lib.php'",
            $source
        );
        $this->assertStringContainsString(
            'local_campus_render_trial_discount_banner(false)',
            $source
        );
    }

    public function test_cart_shows_product_promotion_and_compact_quantity(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $price = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache');
        $this->assertStringContainsString('commerce-cart-line__quantity-badge', $template);
        $this->assertStringContainsString('cartpricehaspromotionbadge', $price);
        $this->assertStringContainsString('cartpricepromotionbadge', $price);
        $this->assertStringContainsString('commerce-cart-price--promotion', $price);
    }

    public function test_summary_contains_original_total_and_reductions(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/presentation/'
            . 'CommerceCartPresenter.php'
        );
        $summary = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/summary.mustache'
        );

        $this->assertIsString($presenter);
        $this->assertStringContainsString(
            'listtotalformatted',
            $presenter
        );
        $this->assertStringContainsString(
            'totalreductionsformatted',
            $presenter
        );

        $this->assertIsString($summary);
        $this->assertStringContainsString(
            '{{listtotalformatted}}',
            $summary
        );
        $this->assertStringContainsString(
            '{{totalreductionsformatted}}',
            $summary
        );
    }
}
