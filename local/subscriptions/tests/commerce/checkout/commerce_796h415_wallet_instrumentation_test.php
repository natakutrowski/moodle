<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h415_wallet_instrumentation_test extends advanced_testcase {
    public function test_checkout_keeps_only_non_secret_server_side_wallet_capability_metadata(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        foreach ([
            "'stripe_registered'",
            "'stripe_available'",
            "'apple_pay_admin_allowed'",
            "'google_pay_admin_allowed'",
            "'publishable_key_present'",
            "'has_express_wallet_candidate'",
        ] as $expected) {
            $this->assertStringContainsString($expected, $checkout);
        }
        $this->assertStringNotContainsString("'secret_key'", $checkout);
    }

    public function test_debug_wallet_ui_has_been_removed_from_customer_template(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        $this->assertStringNotContainsString('data-wallet-debug-status', $template);
        $this->assertStringNotContainsString('data-wallet-debug-log', $template);
        $this->assertStringContainsString('data-checkout-express-wallet-element', $template);
    }
}
