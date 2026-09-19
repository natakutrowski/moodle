<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\market\CommerceMarketCurrencyRecommendationService;

final class commerce_796h1312_market_currency_recommendation_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @dataProvider primary_market_provider
     */
    public function test_primary_markets_are_not_binary_rub_vs_eur(
        string $country,
        string $currency
    ): void {
        $result =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                $country
            );

        self::assertSame(
            $country,
            $result->get_country()
        );
        self::assertSame(
            $currency,
            $result->get_currency()
        );
        self::assertSame(
            'built_in_market',
            $result->get_source()
        );
    }

    public static function primary_market_provider(): array {
        return [
            'France => EUR' => ['FR', 'EUR'],
            'Russia => RUB' => ['RU', 'RUB'],
            'Belarus => RUB' => ['BY', 'RUB'],
            'United States => USD' => ['US', 'USD'],
            'United Kingdom => GBP' => ['GB', 'GBP'],
        ];
    }

    /**
     * @dataProvider euro_area_provider
     */
    public function test_euro_area_markets_recommend_eur(
        string $country
    ): void {
        $result =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                $country
            );

        self::assertSame(
            'EUR',
            $result->get_currency()
        );
        self::assertSame(
            'built_in_market',
            $result->get_source()
        );
    }

    public static function euro_area_provider(): array {
        return [
            ['DE'],
            ['ES'],
            ['IT'],
            ['NL'],
            ['PT'],
            ['IE'],
        ];
    }

    public function test_unknown_market_falls_back_without_language_inference(): void {
        $result =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                'ZZ'
            );

        self::assertSame(
            'ZZ',
            $result->get_country()
        );
        self::assertSame(
            'EUR',
            $result->get_currency()
        );
        self::assertSame(
            'market_fallback',
            $result->get_source()
        );
    }

    public function test_invalid_country_is_normalized_to_unknown_market(): void {
        $result =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                'russian'
            );

        self::assertSame(
            'ZZ',
            $result->get_country()
        );
        self::assertSame(
            'EUR',
            $result->get_currency()
        );
    }

    public function test_configured_map_can_add_new_market_without_code_change(): void {
        set_config(
            'commerce_market_currency_map',
            'CH=CHF,CA=CAD,JP=JPY',
            'local_subscriptions'
        );

        $service =
            new CommerceMarketCurrencyRecommendationService();

        self::assertSame(
            'CHF',
            $service->recommend('CH')->get_currency()
        );
        self::assertSame(
            'CAD',
            $service->recommend('CA')->get_currency()
        );
        self::assertSame(
            'JPY',
            $service->recommend('JP')->get_currency()
        );
        self::assertSame(
            'configured_market',
            $service->recommend('CH')->get_source()
        );
    }

    public function test_configured_map_overrides_built_in_market(): void {
        set_config(
            'commerce_market_currency_map',
            'US=EUR',
            'local_subscriptions'
        );

        $result =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                'US'
            );

        self::assertSame(
            'EUR',
            $result->get_currency()
        );
        self::assertSame(
            'configured_market',
            $result->get_source()
        );
    }

    public function test_malformed_configuration_entries_are_ignored(): void {
        set_config(
            'commerce_market_currency_map',
            'BAD,XX=,=USD,CH=CHF',
            'local_subscriptions'
        );

        $service =
            new CommerceMarketCurrencyRecommendationService();

        self::assertSame(
            'CHF',
            $service->recommend('CH')->get_currency()
        );
        self::assertSame(
            'EUR',
            $service->recommend('XX')->get_currency()
        );
    }

    public function test_market_recommendation_is_only_a_candidate_for_h1311_selection(): void {
        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );

        $recommendation =
            (
                new CommerceMarketCurrencyRecommendationService()
            )->recommend(
                'US'
            );

        $selection =
            (
                new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService()
            )->resolve(
                new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext(
                    available: ['EUR', 'GBP'],
                    marketdefault: $recommendation->get_currency()
                )
            );

        // USD is recommended for the US market, but this commercial surface
        // cannot sell USD. H13.1.1 therefore falls through to Commerce default.
        self::assertSame(
            'USD',
            $recommendation->get_currency()
        );
        self::assertSame(
            'EUR',
            $selection->get_currency()
        );
    }

    public function test_service_contains_no_language_based_currency_rule(): void {
        global $CFG;

        $source =
            file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/classes/commerce/currency/market/'
                . 'CommerceMarketCurrencyRecommendationService.php'
            );

        self::assertStringNotContainsString(
            'current_language',
            $source
        );
        self::assertStringNotContainsString(
            "=== 'ru'",
            $source
        );
    }
}
