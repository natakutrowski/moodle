<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * PayPal REST payment currencies understood by CampusFR.
 *
 * Currency support is not market eligibility. Country / market restrictions
 * are resolved separately and must never be inferred from this list.
 */
final class PayPalSupportedCurrencies {
    private const CURRENCIES = [
        'AUD',
        'BRL',
        'CAD',
        'CHF',
        'CNY',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'HKD',
        'HUF',
        'JPY',
        'MXN',
        'NOK',
        'NZD',
        'PLN',
        'RUB',
        'SEK',
        'SGD',
        'USD',
    ];

    /**
     * @return string[]
     */
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
        $currency = Currency::sanitize($currency);

        return $currency !== ''
            && in_array(
                $currency,
                self::all(),
                true
            );
    }

    private function __construct() {
    }
}
