<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\market;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\currency\Currency;

/**
 * H13.1.2 — recommends a currency from a market/country context.
 *
 * This service deliberately does not know whether a product or checkout
 * surface can actually sell the recommended currency. H13.1.1 remains
 * responsible for filtering the recommendation against available/enabled
 * currencies.
 *
 * Administrators may override or extend the built-in country map with
 * local_subscriptions/commerce_market_currency_map using CSV pairs:
 *
 *     CH=CHF,CA=CAD,JP=JPY
 *
 * Invalid entries are ignored. Explicit configuration wins over built-ins.
 */
final class CommerceMarketCurrencyRecommendationService {
    /** @var array<string, string> */
    private const DEFAULT_COUNTRY_MAP = [
        // Dedicated test/certification markets.
        'US' => 'USD',
        'GB' => 'GBP',
        'RU' => 'RUB',
        'BY' => 'RUB',

        // Euro-area market defaults.
        'AT' => 'EUR',
        'BE' => 'EUR',
        'HR' => 'EUR',
        'CY' => 'EUR',
        'EE' => 'EUR',
        'FI' => 'EUR',
        'FR' => 'EUR',
        'DE' => 'EUR',
        'GR' => 'EUR',
        'IE' => 'EUR',
        'IT' => 'EUR',
        'LV' => 'EUR',
        'LT' => 'EUR',
        'LU' => 'EUR',
        'MT' => 'EUR',
        'NL' => 'EUR',
        'PT' => 'EUR',
        'SK' => 'EUR',
        'SI' => 'EUR',
        'ES' => 'EUR',
    ];

    public function recommend(
        string $country
    ): CommerceMarketCurrencyRecommendation {
        $country =
            $this->normalize_country(
                $country
            );

        $configured =
            $this->configured_map();

        if (
            $country !== 'ZZ'
            && isset($configured[$country])
        ) {
            return new CommerceMarketCurrencyRecommendation(
                $country,
                $configured[$country],
                'configured_market'
            );
        }

        if (
            $country !== 'ZZ'
            && isset(self::DEFAULT_COUNTRY_MAP[$country])
        ) {
            return new CommerceMarketCurrencyRecommendation(
                $country,
                self::DEFAULT_COUNTRY_MAP[$country],
                'built_in_market'
            );
        }

        // Unknown/unmapped markets do not attempt to infer a local currency
        // from language. EUR is the configured Commerce market fallback today;
        // H13.1.1 may still discard it when the current surface cannot sell EUR.
        return new CommerceMarketCurrencyRecommendation(
            $country,
            'EUR',
            'market_fallback'
        );
    }

    private function normalize_country(
        string $country
    ): string {
        $country =
            strtoupper(
                trim($country)
            );

        return preg_match(
            '/^[A-Z]{2}$/',
            $country
        ) === 1
            ? $country
            : 'ZZ';
    }

    /**
     * @return array<string, string>
     */
    private function configured_map(): array {
        $raw =
            trim(
                (string)get_config(
                    'local_subscriptions',
                    'commerce_market_currency_map'
                )
            );

        if ($raw === '') {
            return [];
        }

        $map = [];

        foreach (
            preg_split(
                '/[\r\n,;]+/',
                $raw
            ) ?: []
            as $entry
        ) {
            $entry =
                trim(
                    (string)$entry
                );

            if (
                $entry === ''
                || !str_contains(
                    $entry,
                    '='
                )
            ) {
                continue;
            }

            [$country, $currency] =
                array_map(
                    'trim',
                    explode(
                        '=',
                        $entry,
                        2
                    )
                );

            $country =
                $this->normalize_country(
                    $country
                );
            $currency =
                Currency::sanitize(
                    $currency
                );

            if (
                $country === 'ZZ'
                || $currency === ''
            ) {
                continue;
            }

            $map[$country] =
                $currency;
        }

        return $map;
    }
}
