<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g3_google_pay_stripe_capability_test extends advanced_testcase {
    public function test_stripe_declares_google_pay_as_payment_method_capability(): void {
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
    }

    public function test_google_pay_is_not_declared_by_alfa_or_paypal(): void {
        global $CFG;

        foreach ([
            'alfa/AlfaCommercePaymentProvider.php',
            'paypal/PayPalCommercePaymentProvider.php',
        ] as $relative) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/provider/'
                . $relative
            );

            $this->assertStringNotContainsString(
                'CommercePaymentMethod::GOOGLE_PAY',
                $contents
            );
        }
    }
}
