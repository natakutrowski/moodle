<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h418_wallet_cache_bust_test extends advanced_testcase {
    public function test_checkout_uses_current_stable_wallet_module(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString("'local_subscriptions/checkout_express_wallets'", $checkout);
        $this->assertStringNotContainsString("'local_subscriptions/checkout_express_payments'", $checkout);
    }

    public function test_current_module_keeps_stripe_express_contract_without_debug_signature_dependency(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            "'availablepaymentmethodschange'",
            'expressPaymentMethodTypes(',
            'emailRequired: true',
            'express.mount(mount);',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
