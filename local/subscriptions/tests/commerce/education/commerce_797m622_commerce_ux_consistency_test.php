<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797m622_commerce_ux_consistency_test extends \advanced_testcase {
    public function test_cart_print_reuses_the_same_compact_access_contract_as_cart(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/print.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            '{{> local_subscriptions/customer/access_promise_compact }}',
            $template
        );
        self::assertStringNotContainsString('<strong>{{accesspromisetitle}}</strong>', $template);
    }

    public function test_promotion_join_badge_uses_one_shared_visual_class_on_cart_checkout_and_print(): void {
        global $CFG;

        $cart = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $print = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/print.mustache'
        );

        foreach ([$cart, $checkout, $print] as $template) {
            self::assertIsString($template);
            self::assertStringContainsString(
                'commerce-storefront-price__badge--promotion',
                $template
            );
        }
    }

    public function test_checkout_promotion_line_is_compact_and_has_the_group_icon(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString('commerce-checkout-item__promotion', $template);
        self::assertStringContainsString('fa-user-group', $template);
        self::assertStringContainsString('{{accesspromisepromotionlabel}}', $template);
    }
}
