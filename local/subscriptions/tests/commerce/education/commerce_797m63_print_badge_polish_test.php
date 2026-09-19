<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m63_print_badge_polish_test extends advanced_testcase {
    public function test_print_promotion_badge_is_red_and_access_block_has_spacing(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-cart-print-item__badges .commerce-storefront-price__badge--promotion',
            $css
        );
        self::assertStringContainsString('background: #e7334f !important;', $css);
        self::assertStringContainsString('.commerce-cart-print-item__access {', $css);
        self::assertStringContainsString('margin-top: .9rem;', $css);
    }
}
