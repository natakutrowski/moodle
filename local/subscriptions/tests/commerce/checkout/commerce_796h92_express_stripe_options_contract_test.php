<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h92_express_stripe_options_contract_test extends advanced_testcase {
    public function test_link_requires_card_and_link_in_deferred_elements(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringContainsString(
            "|| allowed(config, 'link')",
            $js
        );
        $this->assertStringContainsString(
            "types.push('card')",
            $js
        );
        $this->assertStringContainsString(
            "types.push('link')",
            $js
        );
    }

    public function test_payment_methods_override_only_controls_device_wallets(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringContainsString(
            'paymentMethods: {',
            $js
        );
        $this->assertStringContainsString(
            'applePay:',
            $js
        );
        $this->assertStringContainsString(
            'googlePay:',
            $js
        );
        $this->assertStringNotContainsString(
            "link:\n                    allowed(",
            $js
        );
        $this->assertStringNotContainsString(
            "klarna:\n                    allowed(",
            $js
        );
    }

    public function test_klarna_uses_supported_pay_button_type(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringContainsString(
            "klarna: 'pay'",
            $js
        );
    }
}
