<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B11.6 cart portrait visual sizing contract. */
final class commerce_cart_portrait_visual_j67b116_test
        extends \advanced_testcase {

    public function test_portrait_modifier_is_applied_to_visual_container(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/cart/page.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'commerce-cart-line__visual--portrait',
            $template
        );
        $this->assertStringContainsString(
            'class="commerce-cart-line__cover"',
            $template
        );
    }

    public function test_fixed_landscape_dimensions_are_overridden(): void {
        global $CFG;
        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        $this->assertStringContainsString('.commerce-cart-line__visual--portrait', $styles);
        $this->assertStringContainsString('aspect-ratio: 4 / 5 !important', $styles);
        $this->assertStringContainsString('height: 100% !important', $styles);
        $this->assertStringContainsString('min-height: 0 !important', $styles);
    }
}
