<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\currency\Currency;

/**
 * H13.2.3 — classifies how strongly customer-country detection may influence
 * one payment method.
 *
 * Geo/IP is advisory by default because VPNs and proxies make it unreliable.
 * Only methods with genuine market eligibility constraints may use country as
 * an eligibility signal.
 */
final class CommercePaymentMarketSignalPolicy {
    public const ADVISORY = 'advisory';
    public const ELIGIBILITY = 'eligibility';

    public function mode(
        string $method,
        string $currency
    ): string {
        $method =
            CommercePaymentMethod::normalise(
                $method
            );
        $currency =
            Currency::sanitize(
                $currency
            );

        if ($currency === '') {
            throw new \coding_exception(
                'Payment market signal policy requires a valid currency.'
            );
        }

        if ($method === CommercePaymentMethod::KLARNA) {
            return self::ELIGIBILITY;
        }

        return self::ADVISORY;
    }

    public function country_is_authoritative_for(
        string $method,
        string $currency
    ): bool {
        return $this->mode(
            $method,
            $currency
        ) === self::ELIGIBILITY;
    }
}
