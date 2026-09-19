<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\availability;

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\currency\Currency;
use local_subscriptions\commerce\payment\policy\CommercePaymentMarketSignalPolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer market eligibility independent from provider routing.
 *
 * Provider capabilities answer "can this PSP execute the method?"
 * This policy answers "is the method appropriate for this customer market?"
 */
final class CommercePaymentMethodMarketEligibility {
    /**
     * Currencies accepted by the Klarna rail used by the current Stripe
     * integration / deferred Express Checkout intent.
     *
     * H13.2.5: do not broaden this list from Klarna's generic market coverage.
     * Stripe rejects the whole Express Checkout initialisation when Klarna is
     * supplied with an unsupported currency (observed notably with USD).
     */
    private const KLARNA_CURRENCIES = [
        'CHF',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'NOK',
        'PLN',
        'RON',
        'SEK',
    ];

    private const KLARNA_CUSTOMER_COUNTRIES = [
        'AU',
        'AT',
        'BE',
        'CA',
        'CZ',
        'DK',
        'FI',
        'FR',
        'GR',
        'DE',
        'IE',
        'IT',
        'NL',
        'NZ',
        'NO',
        'PL',
        'PT',
        'RO',
        'ES',
        'SE',
        'CH',
        'GB',
        'US',
    ];

    public static function supports(
        string $method,
        string $currency,
        ?string $country = null
    ): bool {
        $method =
            CommercePaymentMethod::normalise(
                $method
            );

        $currency =
            Currency::sanitize(
                $currency
            );

        if ($currency === '') {
            return false;
        }

        if (in_array(
            $method,
            [
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::SBERPAY,
                CommercePaymentMethod::MIR_PAY,
            ],
            true
        )) {
            // H13.2.3: RUB-native payment methods are currency-led. IP/country
            // detection is only a hint and may be wrong because of VPN/proxy use.
            // Provider capability remains authoritative for actual execution.
            return $currency === 'RUB';
        }

        if ($method !== CommercePaymentMethod::KLARNA) {
            return true;
        }

        if (
            !in_array(
                $currency,
                self::KLARNA_CURRENCIES,
                true
            )
        ) {
            return false;
        }

        $marketsignal =
            new CommercePaymentMarketSignalPolicy();

        if (
            !$marketsignal
                ->country_is_authoritative_for(
                    $method,
                    $currency
                )
        ) {
            return true;
        }

        // Klarna is the deliberate exception: it has real customer-country
        // eligibility constraints. Missing country keeps architecture checks
        // permissive; runtime checkout normally supplies the detected country.
        if ($country === null || trim($country) === '') {
            return true;
        }

        $country = strtoupper(
            trim($country)
        );

        if (
            !preg_match(
                '/^[A-Z]{2}$/',
                $country
            )
        ) {
            return false;
        }

        return in_array(
            $country,
            self::KLARNA_CUSTOMER_COUNTRIES,
            true
        );
    }

    public static function is_market_dependent(
        string $method
    ): bool {
        return in_array(
            CommercePaymentMethod::normalise($method),
            [
                CommercePaymentMethod::KLARNA,
                CommercePaymentMethod::ALFA_PAY,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::SBERPAY,
                CommercePaymentMethod::MIR_PAY,
            ],
            true
        );
    }

    private function __construct() {
    }
}
