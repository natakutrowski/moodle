<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m631_visual_polish_test extends advanced_testcase {
    public function test_mon_campus_course_copy_selector_does_not_capture_nested_learning_rows(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/customer_hub.css');
        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-customer-hub__course > span:nth-child(2)',
            $css
        );
        self::assertStringNotContainsString(
            '.commerce-customer-hub__course span:nth-child(2) { display:flex;',
            $css
        );
    }

    public function test_cart_print_promotion_badge_has_immediate_consistent_colour_and_spacing(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/print.mustache');
        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-cart-print-item__badges mb-3',
            $template
        );
        self::assertStringContainsString(
            'style="background:#e7334f;color:#fff;"',
            $template
        );
        self::assertStringContainsString(
            'commerce-cart-print-item__access',
            $template
        );
    }
}
