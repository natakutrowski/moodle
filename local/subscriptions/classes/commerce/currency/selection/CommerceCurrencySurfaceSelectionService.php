<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\market\CommerceMarketCountryResolver;
use local_subscriptions\commerce\currency\market\CommerceMarketCurrencyRecommendationService;
use local_subscriptions\currency\Currency;

/**
 * Runtime adapter shared by Storefront/Product/Showroom.
 *
 * H13.1.3 intentionally wires only explicit/user/session/market candidates.
 * Active cart and active Guest Checkout candidates are introduced in H13.1.4,
 * where their authoritative runtime state can be handled consistently.
 */
final class CommerceCurrencySurfaceSelectionService {
    public function resolve(
        array $available,
        string $explicit = '',
        string $userpreference = '',
        string $sessionpreference = '',
        string $commercedefault = 'EUR',
        string $activecart = '',
        string $activeguestcheckout = ''
    ): CommerceCurrencySelectionResult {
        $country = (new CommerceMarketCountryResolver())->resolve();
        $market = (new CommerceMarketCurrencyRecommendationService())
            ->recommend($country);

        return (new CommerceCurrencySelectionService())->resolve(
            new CommerceCurrencySelectionContext(
                available: $available,
                explicit: Currency::sanitize($explicit),
                activecart: Currency::sanitize($activecart),
                activeguestcheckout: Currency::sanitize($activeguestcheckout),
                userpreference: Currency::sanitize($userpreference),
                sessionpreference: Currency::sanitize($sessionpreference),
                marketdefault: $market->get_currency(),
                commercedefault: Currency::sanitize($commercedefault)
            )
        );
    }
}
