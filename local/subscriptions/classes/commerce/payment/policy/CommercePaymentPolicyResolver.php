<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\policy;

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Checkout recommendation policy.
 *
 * Critical rule:
 *   country/market policy ranks methods; provider capabilities decide whether
 *   a method is actually available.
 *
 * In particular, RU/BY does NOT forbid future alternative methods. Card is
 * merely kept as the recommended/reliable route and alternatives remain
 * visible under "other payment methods" when providers really support them.
 */
final class CommercePaymentPolicyResolver {
    public function __construct(
        private readonly CommercePaymentRecommendationProfileRegistry $profiles =
            new CommercePaymentRecommendationProfileRegistry()
    ) {
    }

    /**
     * @param CommercePaymentMethodAvailability[] $availablemethods
     */
    public function resolve(
        string $country,
        string $currency,
        array $availablemethods
    ): CommercePaymentPolicyResult {
        $country = $this->normalise_country($country);
        $currency = Currency::sanitize($currency);

        if ($currency === '') {
            throw new \coding_exception(
                'Payment policy requires a valid currency.'
            );
        }

        $available = [];
        foreach ($availablemethods as $availability) {
            if (!$availability instanceof CommercePaymentMethodAvailability) {
                throw new \coding_exception(
                    'Payment policy received an invalid availability item.'
                );
            }
            if (!$availability->is_available()) {
                continue;
            }
            $available[$availability->get_method()] = $availability;
        }

        $preferredorder = $this->preferred_method_order(
            $country,
            $currency
        );

        $ordered = [];
        foreach ($preferredorder as $method) {
            if (isset($available[$method])) {
                $ordered[] = $available[$method];
                unset($available[$method]);
            }
        }

        // Preserve resolver order for methods not covered by the policy.
        foreach ($availablemethods as $availability) {
            if (
                $availability instanceof CommercePaymentMethodAvailability
                && $availability->is_available()
                && isset($available[$availability->get_method()])
            ) {
                $ordered[] = $availability;
                unset($available[$availability->get_method()]);
            }
        }

        $recommended = $ordered[0]->get_method() ?? null;
        $advicekey = null;

        if (
            $currency === 'RUB'
            && $recommended === CommercePaymentMethod::CARD
        ) {
            $advicekey = 'regional_card_recommended';
        }

        return new CommercePaymentPolicyResult(
            $country,
            $currency,
            $ordered,
            $recommended,
            $advicekey
        );
    }

    /**
     * @return string[]
     */
    /**
     * @return string[]
     */
    private function preferred_method_order(
        string $country,
        string $currency
    ): array {
        return $this->profiles->order(
            $currency,
            $country
        );
    }

    private function normalise_country(string $country): string {
        $country = strtoupper(trim($country));

        return preg_match('/^[A-Z]{2}$/', $country)
            ? $country
            : 'ZZ';
    }
}
