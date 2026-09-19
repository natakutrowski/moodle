<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1313_storefront_showroom_currency_wiring_test extends \advanced_testcase {
    public function test_storefront_product_and_showroom_share_h131_currency_surface_service(): void {
        global $CFG;

        foreach ([
            'digital_catalog.php',
            'storefront_product.php',
            'showroom.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'CommerceCurrencySurfaceSelectionService',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                "in_array(\$country, ['RU', 'BY'], true) ? 'RUB' : 'EUR'",
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'Region::detect_country()',
                $source,
                $relative
            );
        }
    }

    public function test_catalog_resolves_currency_before_guest_cart_identity(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        $selection = strpos(
            $source,
            '$currencyselection = (new CommerceCurrencySurfaceSelectionService())->resolve('
        );
        $customer = strpos(
            $source,
            'CommerceGuestCartCustomerResolver::create()->resolve($currency)'
        );

        self::assertNotFalse($selection);
        self::assertNotFalse($customer);
        self::assertLessThan($customer, $selection);
    }

    public function test_showroom_now_honours_authenticated_storefront_preference(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/showroom.php'
        );

        self::assertStringContainsString(
            "get_user_preferences(\n        'local_subscriptions_storefront_currency'",
            $source
        );
        self::assertStringContainsString(
            "set_user_preference(\n        'local_subscriptions_storefront_currency'",
            $source
        );
    }


    public function test_catalog_selector_filters_product_currencies_by_commerce_registry(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        self::assertStringContainsString(
            '(new CommerceCurrencyRegistry())->enabled()',
            $source
        );
        self::assertStringContainsString(
            'in_array(',
            $source
        );
        self::assertStringContainsString(
            '$availablecurrencies',
            $source
        );
    }

    public function test_showroom_keeps_region_dependency_for_legal_urls_only(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/showroom.php'
        );

        self::assertStringContainsString(
            'use local_subscriptions\\support\\Region;',
            $source
        );
        self::assertStringContainsString(
            'Region::policyUrls()',
            $source
        );
        self::assertStringNotContainsString(
            'Region::detect_country()',
            $source
        );
    }

    public function test_market_country_detection_contains_no_language_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/market/'
            . 'CommerceMarketCountryResolver.php'
        );

        self::assertStringNotContainsString('current_language', $source);
        self::assertStringNotContainsString("=== 'ru'", $source);
        self::assertStringContainsString('HTTP_CF_IPCOUNTRY', $source);
    }

    public function test_surface_adapter_keeps_market_as_candidate_not_authority(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/selection/'
            . 'CommerceCurrencySurfaceSelectionService.php'
        );

        self::assertStringContainsString(
            'CommerceMarketCurrencyRecommendationService',
            $source
        );
        self::assertStringContainsString(
            'marketdefault: $market->get_currency()',
            $source
        );
        self::assertStringContainsString(
            'new CommerceCurrencySelectionService()',
            $source
        );
    }

    public function test_cart_and_checkout_are_now_wired_by_h1314(): void {
        global $CFG;

        foreach ([
            'cart.php',
            'commerce_checkout.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'CommerceCurrencySurfaceSelectionService',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'CommerceCurrencyJourneyStateResolver',
                $source,
                $relative
            );
        }
    }
}
