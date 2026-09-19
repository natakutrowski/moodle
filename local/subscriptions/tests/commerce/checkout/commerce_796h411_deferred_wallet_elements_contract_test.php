<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h411_deferred_wallet_elements_contract_test extends advanced_testcase {
    public function test_deferred_elements_builds_payment_method_types_from_authorized_express_rails(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            'expressPaymentMethodTypes',
            "types.push('card')",
            "types.push('link')",
            "types.push('klarna')",
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }

    public function test_wallet_details_are_submitted_before_intent_creation(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $submit = strpos($js, 'await elements.submit();');
        $intent = strpos($js, 'await initializePayment(');
        $this->assertNotFalse($submit);
        $this->assertNotFalse($intent);
        $this->assertLessThan($intent, $submit);
    }

    public function test_wallet_resolution_uses_stripe_availability_event_and_state_classes(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            "'availablepaymentmethodschange'",
            'setResolvedState(',
            "'is-wallet-ready'",
            "'is-wallet-unavailable'",
            'section.dataset.walletState',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
