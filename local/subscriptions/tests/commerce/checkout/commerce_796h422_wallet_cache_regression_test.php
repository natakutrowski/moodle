<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h422_wallet_cache_regression_test extends advanced_testcase {
    public function test_checkout_uses_current_permanent_express_module(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString("'local_subscriptions/checkout_express_wallets'", $checkout);
        $this->assertStringNotContainsString("'local_subscriptions/checkout_express_payments'", $checkout);
    }

    public function test_current_module_keeps_validated_dynamic_stripe_contract(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            "'availablepaymentmethodschange'",
            'expressPaymentMethodTypes(',
            "types.push('card')",
            "allowed(config, 'apple_pay')",
            "allowed(config, 'google_pay')",
            "allowed(config, 'link')",
            "allowed(config, 'klarna')",
            'express.mount(mount);',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
