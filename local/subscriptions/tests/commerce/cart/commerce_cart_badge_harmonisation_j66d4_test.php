<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6D4 badge and product-card visual harmonisation. */
final class commerce_cart_badge_harmonisation_j66d4_test
        extends \advanced_testcase {

    public function test_cart_reuses_storefront_product_type_labels(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/presentation/'
            . 'CommerceCartPresenter.php'
        );

        $this->assertIsString($presenter);
        $this->assertStringContainsString(
            'commerce_product_type_course_access',
            $presenter
        );
        $this->assertStringContainsString(
            'commerce_product_type_digital_download',
            $presenter
        );
        $this->assertStringContainsString(
            'commerce_product_type_bundle',
            $presenter
        );
        $this->assertStringNotContainsString(
            'commerce_cart_badge_trial',
            $presenter
        );
    }

    public function test_cart_uses_exact_storefront_type_badge_markup(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/page.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'badge rounded-pill text-bg-light border commerce-product-type-badge',
            $template
        );
        $this->assertStringNotContainsString(
            'commerce-cart-line__type-badge',
            $template
        );
    }

    public function test_cart_visual_and_boutique_borders_are_strengthened(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertIsString($styles);
        $this->assertStringContainsString(
            'grid-template-columns: 132px minmax(0, 1fr) auto',
            $styles
        );
        $this->assertStringContainsString(
            'article.commerce-product-card.card',
            $styles
        );
        $this->assertStringContainsString(
            'border: 1px solid #d4dae4 !important',
            $styles
        );
    }
}
