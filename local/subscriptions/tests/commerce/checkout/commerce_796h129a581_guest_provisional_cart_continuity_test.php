<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a581_guest_provisional_cart_continuity_test extends \advanced_testcase {
    public function test_all_public_cart_surfaces_use_one_customer_resolver(): void {
        global $CFG;

        foreach ([
            'cart.php',
            'cart_action.php',
            'cart_print.php',
            'digital_catalog.php',
            'storefront_product.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'CommerceGuestCartCustomerResolver',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'CommerceGuestCartCustomerResolver::create()->resolve($currency)',
                $source,
                $relative
            );
        }
    }

    public function test_resolver_only_promotes_session_owned_provisional_cart(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );

        self::assertStringContainsString(
            "'provisional'",
            $source
        );
        self::assertStringContainsString(
            "'payment_pending'",
            $source
        );
        self::assertStringContainsString(
            '$session->get_user_id() === null',
            $source
        );
        self::assertStringContainsString(
            '$session->get_currency() !== $currency',
            $source
        );
        self::assertStringNotContainsString(
            "'existing_account'",
            $source
        );
    }

    public function test_cart_mutations_refresh_durable_guest_snapshot(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );

        self::assertStringContainsString(
            '$cartcustomerresolver->synchronize(',
            $action
        );
        self::assertStringContainsString(
            '$result->get_cart()',
            $action
        );
        self::assertStringContainsString(
            "'guest_cart_snapshot'",
            $resolver
        );
        self::assertStringContainsString(
            "['customerid' => 0]",
            $resolver
        );
    }

    public function test_provisional_currency_switch_moves_same_guest_cart_identity(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringContainsString(
            '$targetcurrency,',
            $action
        );
        self::assertStringContainsString(
            '$service->open(',
            $action
        );
        self::assertStringContainsString(
            "true\n        );",
            $action
        );
    }

    public function test_existing_account_cart_transfer_still_requires_real_moodle_login(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart.php'
        );

        self::assertStringContainsString(
            'if (isloggedin() && !isguestuser() && $customerid > 0) {',
            $source
        );
    }
}
