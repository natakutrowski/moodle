<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/**
 * Server-authoritative free-amount policy for Donation products.
 *
 * A catalogue Donation price is the contribution unit for its currency.
 * Customer intent is stored as an integer number of units; Commerce quantity
 * deliberately remains 1 so donation pricing never abuses quantity semantics.
 */
final class CommerceDonationAmountPolicy {
    public const METADATA_UNITS = 'donation_units';
    public const DEFAULT_UNITS = 10;
    public const DEFAULT_MAX_UNITS = 100;

    public function max_units(): int {
        $configured = (int)get_config('local_subscriptions', 'commerce_donation_max_units');
        return $configured > 0 ? $configured : self::DEFAULT_MAX_UNITS;
    }

    public function default_units(): int {
        return min(self::DEFAULT_UNITS, $this->max_units());
    }

    /** @return int[] */
    public function preset_units(): array {
        $maxunits = $this->max_units();
        $presets = array_values(array_filter(
            [5, 10, 25, 50],
            static fn(int $units): bool => $units <= $maxunits
        ));
        return $presets !== [] ? $presets : [1];
    }

    public function require_units(mixed $raw): int {
        if (is_int($raw)) {
            $units = $raw;
        } else if (is_string($raw) && preg_match('/^[1-9][0-9]*$/', trim($raw))) {
            $units = (int)trim($raw);
        } else {
            throw new \coding_exception('Donation units must be a positive integer.');
        }

        if ($units < 1 || $units > $this->max_units()) {
            throw new \coding_exception('Donation units are outside the allowed range.');
        }
        return $units;
    }

    public function canonical_metadata(array $metadata, mixed $rawunits): array {
        foreach (array_keys($metadata) as $key) {
            if (str_starts_with(strtolower((string)$key), 'donation_')) {
                unset($metadata[$key]);
            }
        }
        $metadata[self::METADATA_UNITS] = $this->require_units($rawunits);
        return $metadata;
    }

    public function units_from_metadata(array $metadata): int {
        return $this->require_units($metadata[self::METADATA_UNITS] ?? 1);
    }

    public function amount(CommerceMoney $unitprice, int $units): CommerceMoney {
        $units = $this->require_units($units);
        $minor = $unitprice->get_amount_minor();
        if ($minor <= 0) {
            throw new \coding_exception('A Donation catalogue unit price must be positive.');
        }
        if ($minor > intdiv(PHP_INT_MAX, $units)) {
            throw new \coding_exception('Donation amount exceeds the supported monetary range.');
        }
        return CommerceMoney::from_minor($minor * $units, $unitprice->get_currency());
    }
}
