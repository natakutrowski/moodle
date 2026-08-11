<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6D3 compact cards and translated product-type badges. */
final class commerce_cart_cards_j66d3_test extends \advanced_testcase {

    public function test_cart_uses_translated_type_badges(): void {
        global $CFG;
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/cart/presentation/CommerceCartPresenter.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $this->assertStringContainsString('commerce_product_type_course_access', $presenter);
        $this->assertStringContainsString('commerce_product_type_digital_download', $presenter);
        $this->assertStringContainsString('commerce_product_type_bundle', $presenter);
        $this->assertStringContainsString('commerce-product-type-badge', $template);
    }

    public function test_promotion_wording_is_product_agnostic(): void {
        global $CFG;

        $french = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        $this->assertIsString($french);
        $this->assertStringContainsString(
            "\$string['commerce_trial_storefront_product_promotion'] = 'Promotion';",
            $french
        );
        $this->assertStringContainsString(
            "\$string['commerce_trial_storefront_initial_price'] = 'Prix initial';",
            $french
        );
    }

    public function test_cart_body_and_cards_have_compact_defined_styles(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/page.mustache'
        );
        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce-cart-line__body',
            $template
        );

        $this->assertIsString($styles);
        $this->assertStringContainsString(
            '.commerce-cart-line.card',
            $styles
        );
        $this->assertStringContainsString(
            'border: 1px solid #d9dee7',
            $styles
        );
        $this->assertStringContainsString(
            '.commerce-storefront .commerce-product-card',
            $styles
        );
    }
}
