<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1351_mobile_money_integrity_test extends \advanced_testcase {
    public function test_cart_and_checkout_money_values_never_wrap(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            ".commerce-cart-summary__totals dd,\n.commerce-checkout__totals dd",
            $css
        );
        self::assertStringContainsString(
            'white-space: nowrap;',
            $css
        );
        self::assertStringContainsString(
            'flex: 0 0 auto;',
            $css
        );
    }

    public function test_labels_keep_permission_to_wrap_before_amounts(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            ".commerce-cart-summary__totals dt,\n.commerce-checkout__totals dt",
            $css
        );
        self::assertStringContainsString(
            'min-width: 0;',
            $css
        );
    }

    public function test_historic_discount_selector_is_normalised(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringNotContainsString(
            ".commerce-cart-summary__totals\n.commerce-cart-summary__discount",
            $css
        );
        self::assertStringContainsString(
            '.commerce-cart-summary__totals .commerce-cart-summary__discount + .commerce-cart-summary__discount',
            $css
        );
    }
}
