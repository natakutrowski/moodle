<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6D1 Boutique and cart-line stabilisation contract. */
final class commerce_storefront_cart_layout_j66d1_test
        extends \advanced_testcase {

    public function test_boutique_header_has_no_redundant_eyebrow(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/catalog.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringNotContainsString(
            '<div class="commerce-storefront__eyebrow">CampusFR</div>',
            $template
        );
        $this->assertStringContainsString(
            '{{{trialbannerhtml}}}',
            $template
        );
        $this->assertStringContainsString(
            'commerce-cart-trigger--prominent',
            $template
        );

        $bannerposition = strpos($template, '{{{trialbannerhtml}}}');
        $filterposition = strpos($template, 'commerce-storefront__filters');
        $cartposition = strpos($template, 'commerce-cart-trigger--prominent');

        $this->assertIsInt($bannerposition);
        $this->assertIsInt($filterposition);
        $this->assertIsInt($cartposition);
        $this->assertLessThan($filterposition, $bannerposition);
        $this->assertLessThan($cartposition, $filterposition);
    }

    public function test_cart_line_footer_has_three_aligned_actions(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/page.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce-cart-line__action--quantity',
            $template
        );
        $this->assertStringContainsString(
            'commerce-cart-line__action--details',
            $template
        );
        $this->assertStringContainsString(
            'fa-arrow-up-right-from-square',
            $template
        );
        $this->assertStringContainsString(
            'commerce-cart-line__action--remove',
            $template
        );
    }

    public function test_trial_banner_is_captured_before_rendering(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$contextdata['trialbannerhtml']",
            $source
        );
        $this->assertStringContainsString(
            'local_campus_render_trial_discount_banner(false)',
            $source
        );
    }
}
