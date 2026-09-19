<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\selection\CommerceCurrencyAvailabilityService;

final class commerce_796h1315_currency_persistence_cleanup_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );
    }

    public function test_availability_intersection_preserves_surface_order_and_filters_disabled(): void {
        $available = (new CommerceCurrencyAvailabilityService())->enabled_from(
            ['JPY', 'GBP', 'USD', 'TND', 'EUR']
        );

        self::assertSame(
            ['GBP', 'USD', 'EUR'],
            $available
        );
    }

    public function test_cart_action_no_longer_uses_legacy_currency_resolver(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringNotContainsString(
            'CommerceShowroomCurrencyResolver::resolve(',
            $source
        );
        self::assertStringContainsString(
            'require_enabled(',
            $source
        );
        self::assertStringContainsString(
            'CommerceCurrencyAvailabilityService',
            $source
        );
    }

    public function test_cart_action_persists_successful_currency_for_session_and_user(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringContainsString(
            '$SESSION->local_subscriptions_storefront_currency = $currency;',
            $source
        );
        self::assertStringContainsString(
            "set_user_preference(\n            'local_subscriptions_storefront_currency'",
            $source
        );
    }

    public function test_showroom_ajax_rejects_config_disabled_price_currencies(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/showroom_prices.php'
        );

        self::assertStringContainsString(
            'CommerceCurrencyAvailabilityService',
            $source
        );
        self::assertStringContainsString(
            'enabled_from(',
            $source
        );
    }

    public function test_showroom_ajax_persists_authenticated_explicit_choice(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/showroom_prices.php'
        );

        self::assertStringContainsString(
            "set_user_preference(\n            'local_subscriptions_storefront_currency'",
            $source
        );
    }

    public function test_public_currency_selection_surfaces_have_no_legacy_binary_fallback(): void {
        global $CFG;

        foreach ([
            'digital_catalog.php',
            'storefront_product.php',
            'showroom.php',
            'cart.php',
            'cart_action.php',
            'commerce_checkout.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString(
                "['RU', 'BY'], true) ? 'RUB' : 'EUR'",
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'CommerceShowroomCurrencyResolver::resolve(',
                $source,
                $relative
            );
        }
    }
}
