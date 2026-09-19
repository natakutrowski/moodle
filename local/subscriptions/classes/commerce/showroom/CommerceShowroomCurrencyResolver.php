<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\currency\Currency;
use local_subscriptions\support\Region;

/** Resolves active Commerce currencies consistently for Showroom and checkout actions. */
final class CommerceShowroomCurrencyResolver {
    /** @return string[] */
    public static function active_currencies(\moodle_database $db): array {
        $currencies = $db->get_fieldset_sql(
            'SELECT DISTINCT UPPER(currency)
               FROM {local_subs_commerce_prod_price}
              WHERE active = 1
           ORDER BY UPPER(currency)'
        );

        $registry = new CommerceCurrencyRegistry();
        $enabled = $registry->enabled();
        $currencies = array_values(array_unique(array_filter(array_map(
            static fn(mixed $value): string => Currency::sanitize((string)$value),
            $currencies
        ))));
        $currencies = array_values(array_intersect($enabled, $currencies));

        return $currencies !== [] ? $currencies : $enabled;
    }

    /** @param string[] $available */
    public static function resolve(
        array $available,
        string $requested = '',
        string $stored = ''
    ): string {
        $registry = new CommerceCurrencyRegistry();
        $enabled = $registry->enabled();
        $available = array_values(array_unique(array_filter(array_map(
            static fn(string $value): string => Currency::sanitize($value),
            $available
        ))));
        $available = array_values(array_intersect($enabled, $available));
        if ($available === []) {
            $available = $enabled;
        }

        $requested = Currency::sanitize($requested);
        if ($requested !== '' && in_array($requested, $available, true)) {
            return $requested;
        }

        $stored = Currency::sanitize($stored);
        if ($stored !== '' && in_array($stored, $available, true)) {
            return $stored;
        }

        $candidate = in_array(strtoupper(Region::detect_country()), ['RU', 'BY'], true)
            ? 'RUB'
            : 'EUR';

        if (in_array($candidate, $available, true)) {
            return $candidate;
        }

        return $available[0];
    }
}
