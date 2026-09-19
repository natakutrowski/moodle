<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g2_apple_pay_stripe_capability_test extends advanced_testcase {
    public function test_stripe_declares_card_apple_pay_and_google_pay_after_g3(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethod::CARD',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentMethod::APPLE_PAY',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentMethod::GOOGLE_PAY',
            $contents
        );
        $this->assertStringContainsString(
            "'commerce_payment_method'",
            $contents
        );
    }

    public function test_legacy_stripe_checkout_keeps_apple_pay_on_card_rail(): void {
        global $CFG;

        $bridge = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );
        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/stripe/StripeGateway.php'
        );

        $this->assertStringContainsString(
            "'preferred_payment_method'",
            $bridge
        );
        $this->assertStringContainsString(
            "\$preferredPaymentMethod === 'apple_pay'",
            $gateway
        );
        $this->assertStringContainsString(
            "\$params['payment_method_types'] = ['card'];",
            $gateway
        );
        $this->assertStringContainsString(
            "\$params['metadata']['commerce_payment_method'] = 'apple_pay';",
            $gateway
        );
    }
}
