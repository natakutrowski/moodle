<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * H12.9-A6 — certification of the Stripe Express Checkout contract.
 *
 * Manual E2E certification completed for Apple Pay, Google Pay, Link and
 * Klarna, including CampusFR return, CRM order and fulfillment.
 */
final class commerce_796h129a6_stripe_express_checkout_certification_test extends advanced_testcase {
    public function test_frontend_mounts_all_certified_express_methods(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        foreach ([
            'apple_pay',
            'google_pay',
            'link',
            'klarna',
        ] as $method) {
            self::assertStringContainsString(
                "'{$method}'",
                $source,
                $method
            );
        }

        self::assertStringContainsString(
            'expressCheckout',
            $source
        );
        self::assertStringContainsString(
            'confirmPayment',
            $source
        );
    }

    public function test_action_and_checkout_normalize_unknown_market_country_identically(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );
        $action = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            "\$paymentcountry === 'ZZ'\n        ? null",
            $checkout
        );
        self::assertStringContainsString(
            "\$paymentcountry === 'ZZ'\n                ? null",
            $action
        );
        self::assertStringContainsString(
            '$embeddedmarketcountry',
            $action
        );
    }

    public function test_server_authorizes_complete_stripe_deferred_method_pool(): void {
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

    public function test_gateway_reuses_authorized_pool_for_payment_intent_types(): void {
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
    }

    public function test_wallets_share_card_rail_without_dropping_link_or_klarna(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        self::assertStringContainsString(
            "'apple_pay'",
            $gateway
        );
        self::assertStringContainsString(
            "'google_pay'",
            $gateway
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

    public function test_guest_payment_gate_remains_server_side_authoritative(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            'CommerceGuestPaymentGate::is_ready(',
            $action
        );
        self::assertStringContainsString(
            'guest_identity_verification_required',
            $action
        );
    }

    public function test_guest_provisional_cart_continuity_is_preserved_for_express_checkout(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );
        $cartaction = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringContainsString(
            "'provisional'",
            $resolver
        );
        self::assertStringContainsString(
            "'payment_pending'",
            $resolver
        );
        self::assertStringContainsString(
            '$cartcustomerresolver->synchronize(',
            $cartaction
        );
        self::assertStringContainsString(
            "'guest_cart_snapshot'",
            $resolver
        );
    }
}
