<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a652_stripe_deferred_method_contract_test extends advanced_testcase {
    public function test_action_normalizes_unknown_country_like_checkout_page(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );
        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "\$paymentmarketcountry =\n    \$paymentcountry === 'ZZ'\n        ? null",
            $checkout
        );
        self::assertStringContainsString(
            "\$embeddedmarketcountry =\n            \$paymentcountry === 'ZZ'\n                ? null",
            $action
        );
        self::assertStringContainsString(
            "\$currency,\n                    \$embeddedmethod,\n                    \$embeddedmarketcountry",
            $action
        );
    }

    public function test_action_authorizes_all_stripe_methods_used_by_deferred_elements(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        foreach ([
            'CommercePaymentMethod::CARD',
            'CommercePaymentMethod::APPLE_PAY',
            'CommercePaymentMethod::GOOGLE_PAY',
            'CommercePaymentMethod::LINK',
            'CommercePaymentMethod::KLARNA',
        ] as $method) {
            self::assertStringContainsString(
                $method,
                $action
            );
        }

        self::assertStringContainsString(
            "'embedded_payment_methods' =>",
            $action
        );
        self::assertStringContainsString(
            '$embeddedmethods',
            $action
        );
    }

    public function test_gateway_builds_intent_types_from_action_authorized_pool(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        self::assertStringContainsString(
            'foreach ($requestedembeddedmethods as $embeddedmethod)',
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
    }
}
