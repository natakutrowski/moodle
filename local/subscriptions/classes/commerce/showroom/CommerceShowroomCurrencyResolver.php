<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

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

        $currencies = array_values(array_unique(array_filter(array_map(
            static fn(mixed $value): string => strtoupper(trim((string)$value)),
            $currencies
        ))));

        return $currencies !== [] ? $currencies : ['EUR', 'RUB'];
    }

    /** @param string[] $available */
    public static function resolve(
        array $available,
        string $requested = '',
        string $stored = ''
    ): string {
        $available = array_values(array_unique(array_filter(array_map(
            static fn(string $value): string => strtoupper(trim($value)),
            $available
        ))));
        if ($available === []) {
            $available = ['EUR', 'RUB'];
        }

        $requested = strtoupper(trim($requested));
        if ($requested !== '' && in_array($requested, $available, true)) {
            return $requested;
        }

        $stored = strtoupper(trim($stored));
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
