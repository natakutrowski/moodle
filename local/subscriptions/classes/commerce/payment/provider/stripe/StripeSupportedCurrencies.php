<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\stripe;

use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Stripe presentment currencies explicitly certified by CampusFR Commerce.
 *
 * This is not a market list and not a list of every currency Stripe supports.
 * It is the subset of currencies understood by CampusFR that we currently
 * allow on the Stripe route.
 *
 * RUB and BYN are intentionally excluded from Stripe routing because the
 * current CampusFR payment policy uses Alfa for the restricted RU/BY route.
 */
final class StripeSupportedCurrencies {
    private const CURRENCIES = [
        'AED',
        'ARS',
        'AUD',
        'BRL',
        'CAD',
        'CHF',
        'COP',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'HKD',
        'HUF',
        'INR',
        'JPY',
        'KRW',
        'MXN',
        'NOK',
        'NZD',
        'PLN',
        'RON',
        'SEK',
        'SGD',
        'USD',
        'ZAR',
    ];

    /** @return string[] */
    public static function all(): array {
        return array_values(
            array_filter(
                self::CURRENCIES,
                static fn(string $currency): bool =>
                    Currency::is_known($currency)
            )
        );
    }

    public static function supports(string $currency): bool {
        return in_array(
            Currency::sanitize($currency),
            self::all(),
            true
        );
    }

    private function __construct() {
    }
}
