<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B11.5 Upgrade parity and cart portrait layout. */
final class commerce_upgrade_parity_j67b115_test
        extends \advanced_testcase {

    public function test_storefront_upgrade_badges_show_trial_and_initial_promotion(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            '{{commercialtrialpercent}}',
            $template
        );
        $this->assertStringContainsString(
            '{{commercialpromotionpercent}}%',
            $template
        );
        $this->assertStringNotContainsString(
            '{{commercialdiscountpercentage}}%',
            $template
        );
    }

    public function test_upgrade_path_is_rendered_next_to_price_label(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_price.mustache'
        );

        $this->assertStringContainsString(
            'commerce-storefront-price__heading--upgrade',
            $template
        );
        $this->assertStringContainsString(
            '{{upgradefromlabel}}',
            $template
        );
        $this->assertStringContainsString(
            '{{upgradetolabel}}',
            $template
        );
    }

    public function test_storefront_breakdown_uses_precise_promotion_label(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/pricing/'
            . 'CommerceStorefrontCommercialPricingPresenter.php'
        );

        $this->assertStringContainsString(
            'commerce_pricing_initial_promotion_percent',
            $source
        );
    }

    public function test_cart_uses_portrait_cover_layout(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        $this->assertStringContainsString('commerce-cart-line__visual--portrait', $template);
        $this->assertStringContainsString('aspect-ratio: 4 / 5 !important', $styles);
        $this->assertStringContainsString('object-fit: cover', $styles);
    }
}
