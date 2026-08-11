<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_checkout_simplification_j14a_test extends \advanced_testcase {
    public function test_guest_identity_is_collected_inside_unified_checkout(): void {
        global $CFG;

        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $legacy = file_get_contents($CFG->dirroot . '/local/subscriptions/guest_checkout.php');

        self::assertStringContainsString('showguestidentity', $checkout);
        self::assertStringContainsString('data-guest-checkout-form', $template);
        self::assertStringContainsString('name="email"', $template);
        self::assertStringContainsString('name="firstname"', $template);
        self::assertStringContainsString('name="lastname"', $template);
        self::assertStringContainsString("'/local/subscriptions/commerce_checkout.php'", $legacy);
        self::assertStringNotContainsString('checkout/guest_identity', $legacy);
    }

    public function test_purchase_flow_is_shared_with_order_result(): void {
        global $CFG;

        $cartaction = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php');
        $result = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');

        self::assertStringContainsString('CommercePurchaseFlow::DIRECT', $cartaction);
        self::assertStringContainsString("'purchase_flow' => \$flow", $checkout);
        self::assertStringContainsString("'purchase_flow' => \$flow", $action);
        self::assertStringContainsString('CommercePurchaseFlow::result_steps', $result);
    }

    public function test_authenticated_checkout_does_not_render_identity_fields(): void {
        global $CFG;

        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        self::assertStringContainsString('$showguestidentity = $isguestcheckout', $checkout);
        self::assertStringContainsString("'showguestidentity' => \$showguestidentity", $checkout);
    }
}
