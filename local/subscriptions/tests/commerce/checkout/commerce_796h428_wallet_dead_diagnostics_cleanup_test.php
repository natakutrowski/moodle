<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h428_wallet_dead_diagnostics_cleanup_test extends advanced_testcase {
    public function test_dead_diagnostics_are_removed_while_bounded_probe_timeout_remains(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $this->assertStringNotContainsString('console.info', $js);
        $this->assertStringNotContainsString('serverDiagnostics', $js);
        $this->assertStringContainsString('12000', $js);
    }

    public function test_validated_stripe_lifecycle_is_present_with_dynamic_method_types(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            'stripe.elements({',
            "'expressCheckout'",
            "'availablepaymentmethodschange'",
            "'confirm'",
            'express.mount(mount);',
            'await elements.submit();',
            'confirmPayment({',
            'expressPaymentMethodTypes(',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
