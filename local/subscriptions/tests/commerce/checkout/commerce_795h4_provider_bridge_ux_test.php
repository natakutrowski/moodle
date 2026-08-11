<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static integration guards for 7.95H4. */
final class commerce_795h4_provider_bridge_ux_test extends \advanced_testcase {
    public function test_shared_stepper_is_used_by_cart_and_checkout(): void {
        $root = dirname(__DIR__, 3);
        $partial = file_get_contents($root . '/templates/checkout/steps.mustache');
        $cart = file_get_contents($root . '/templates/cart/page.mustache');
        $checkout = file_get_contents($root . '/templates/checkout/page.mustache');

        $this->assertStringContainsString('commerce-checkout-steps__marker', $partial);
        $this->assertStringContainsString('{{> local_subscriptions/checkout/steps}}', $cart);
        $this->assertStringContainsString('{{> local_subscriptions/checkout/steps}}', $checkout);
    }

    public function test_provider_failures_are_logged_with_a_reference_and_redirected_cleanly(): void {
        $root = dirname(__DIR__, 3);
        $action = file_get_contents($root . '/commerce_checkout_action.php');

        $this->assertStringContainsString('[local_subscriptions][checkout_provider]', $action);
        $this->assertStringContainsString("'paymenterror' => \$reference", $action);
        $this->assertStringNotContainsString('debugging($exception->getMessage()', $action);
    }
}
