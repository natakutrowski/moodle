<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h41_main_checkout_wallet_surface_test extends advanced_testcase {
    public function test_main_checkout_mounts_real_stripe_express_checkout_surface(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString('data-checkout-express-wallet-element', $template);
        $this->assertStringContainsString("'expressCheckout'", $js);
        $this->assertStringContainsString("'availablepaymentmethodschange'", $js);
        $this->assertStringContainsString("'confirm'", $js);
    }

    public function test_express_methods_are_split_from_standard_radio_method_list(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString('$expresspaymentmethods = array_values(', $checkout);
        $this->assertStringContainsString('!in_array(', $checkout);
        $this->assertStringContainsString('$expresspaymentmethods,', $checkout);
    }
}
