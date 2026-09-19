<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\currency\Currency;

/**
 * H13.2.1 — provider-agnostic payment-method recommendation profiles.
 *
 * Availability remains authoritative. These profiles only express preferred
 * presentation order for methods that are already genuinely available.
 *
 * The registry is deliberately multi-currency: it is not a RUB-vs-EUR branch.
 */
final class CommercePaymentRecommendationProfileRegistry {
    /**
     * Local-currency profile.
     *
     * Card remains the safest universal primary route. RUB-native alternatives
     * follow immediately, ahead of cross-border fallbacks.
     *
     * @var string[]
     */
    private const RUB = [
        CommercePaymentMethod::CARD,
        CommercePaymentMethod::SBP,
        CommercePaymentMethod::ALFA_PAY,
        CommercePaymentMethod::SBERPAY,
        CommercePaymentMethod::MIR_PAY,
        CommercePaymentMethod::PAYPAL,
        CommercePaymentMethod::APPLE_PAY,
        CommercePaymentMethod::GOOGLE_PAY,
        CommercePaymentMethod::LINK,
        CommercePaymentMethod::KLARNA,
    ];

    /**
     * Stripe/global-card markets.
     *
     * Express Checkout will device-filter Apple Pay / Google Pay at runtime.
     * Putting wallets first here makes the domain recommendation honest while
     * the checkout surface remains free to render Express separately.
     *
     * @var string[]
     */
    private const GLOBAL_CARD = [
        CommercePaymentMethod::APPLE_PAY,
        CommercePaymentMethod::GOOGLE_PAY,
        CommercePaymentMethod::LINK,
        CommercePaymentMethod::CARD,
        CommercePaymentMethod::PAYPAL,
        CommercePaymentMethod::KLARNA,
        CommercePaymentMethod::ALFA_PAY,
        CommercePaymentMethod::SBP,
        CommercePaymentMethod::SBERPAY,
        CommercePaymentMethod::MIR_PAY,
    ];

    /** @var array<string, string[]> */
    private const CURRENCY_PROFILES = [
        'EUR' => self::GLOBAL_CARD,
        'USD' => self::GLOBAL_CARD,
        'GBP' => self::GLOBAL_CARD,
        'RUB' => self::RUB,
    ];

    /**
     * Return a complete preferred order for a currency/market.
     *
     * Country is accepted now so later H13.2 phases can introduce market
     * refinements without changing the policy contract. Method eligibility
     * itself remains in CommercePaymentMethodMarketEligibility.
     *
     * @return string[]
     */
    public function order(
        string $currency,
        string $country = 'ZZ'
    ): array {
        $currency = Currency::sanitize($currency);

        if ($currency === '') {
            throw new \coding_exception(
                'Payment recommendation requires a valid currency.'
            );
        }

        $country = strtoupper(trim($country));
        if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            $country = 'ZZ';
        }

        $preferred =
            self::CURRENCY_PROFILES[$currency]
            ?? CommercePaymentMethodCatalogue::keys();

        // Always return every catalogue method exactly once. This keeps the
        // registry extensible if a future method is added but not yet profiled.
        $result = [];
        foreach (array_merge(
            $preferred,
            CommercePaymentMethodCatalogue::keys()
        ) as $method) {
            if (!in_array($method, $result, true)) {
                $result[] = $method;
            }
        }

        return $result;
    }
}
