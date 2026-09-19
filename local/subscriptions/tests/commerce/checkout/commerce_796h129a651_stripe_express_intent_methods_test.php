<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a651_stripe_express_intent_methods_test extends advanced_testcase {
    public function test_express_intent_reuses_complete_authorized_method_pool(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        self::assertStringContainsString(
            '$requestedembeddedmethods',
            $gateway
        );
        self::assertStringContainsString(
            'foreach ($requestedembeddedmethods as $embeddedmethod)',
            $gateway
        );
        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'card';",
            $gateway
        );
        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'link';",
            $gateway
        );
        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'klarna';",
            $gateway
        );
        self::assertStringContainsString(
            'array_unique(',
            $gateway
        );
        self::assertStringContainsString(
            "'payment_method_types' =>",
            $gateway
        );
        self::assertStringContainsString(
            '$paymentmethodtypes',
            $gateway
        );
    }

    public function test_selected_apple_pay_no_longer_forces_card_only_when_pool_is_present(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        $pool = strpos(
            $gateway,
            'foreach ($requestedembeddedmethods as $embeddedmethod)'
        );
        $fallback = strpos(
            $gateway,
            'if ($paymentmethodtypes === [])'
        );

        self::assertNotFalse($pool);
        self::assertNotFalse($fallback);
        self::assertGreaterThan($pool, $fallback);
    }

    public function test_frontend_and_backend_share_same_method_vocabulary(): void {
        global $CFG;

        $frontend = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        foreach (['apple_pay', 'google_pay', 'link', 'klarna'] as $method) {
            self::assertStringContainsString(
                "'{$method}'",
                $frontend
            );
            self::assertStringContainsString(
                "'{$method}'",
                $gateway
            );
        }
    }
}
