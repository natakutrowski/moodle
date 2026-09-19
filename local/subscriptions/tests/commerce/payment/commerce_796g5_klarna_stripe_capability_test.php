<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g5_klarna_stripe_capability_test extends advanced_testcase {
    public function test_stripe_declares_klarna_capability(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethod::KLARNA',
            $contents
        );
    }

    public function test_alfa_and_paypal_do_not_declare_klarna(): void {
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
                'CommercePaymentMethod::KLARNA',
                $contents
            );
        }
    }
}
