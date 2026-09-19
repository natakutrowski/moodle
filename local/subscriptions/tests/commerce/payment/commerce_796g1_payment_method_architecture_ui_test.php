<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g1_payment_method_architecture_ui_test extends advanced_testcase {
    public function test_architecture_matrix_is_catalogue_driven(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethodCatalogue::keys()',
            $contents
        );
        $this->assertStringNotContainsString(
            "['card', 'apple_pay', 'google_pay', 'paypal']",
            $contents
        );
    }

    public function test_link_and_klarna_are_stripe_capabilities_after_g5(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethod::LINK',
            $stripe
        );
        $this->assertStringContainsString(
            'CommercePaymentMethod::KLARNA',
            $stripe
        );

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
                'CommercePaymentMethod::LINK',
                $contents
            );
            $this->assertStringNotContainsString(
                'CommercePaymentMethod::KLARNA',
                $contents
            );
        }
    }
}
