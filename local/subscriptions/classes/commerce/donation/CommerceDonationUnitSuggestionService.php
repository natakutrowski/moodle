<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\CommerceFxRateBook;
use local_subscriptions\currency\Currency;

/**
 * Administrative helper that suggests human-readable Donation units.
 *
 * FX is advisory only. The saved catalogue price remains authoritative for all
 * cart and checkout calculations.
 */
final class CommerceDonationUnitSuggestionService {
    public function __construct(private readonly CommerceFxRateBook $rates) {
    }

    /**
     * Suggest a major-unit step from the order of magnitude of the FX rate.
     * Returns 1, 10, 100, 1000...; never a checkout price.
     */
    public function suggest_major_unit(string $currency): ?int {
        $currency = Currency::sanitize($currency);
        if ($currency === '' || !Currency::is_known($currency)) {
            return null;
        }

        $rate = $this->rates->rate_for($currency);
        if ($rate === null) {
            return null;
        }
        $value = (float)$rate;
        if (!is_finite($value) || $value <= 0) {
            return null;
        }

        // A readable contribution unit is the lower power of ten of the local
        // value of one base-currency unit. Examples: 1.1 => 1, 11 => 10,
        // 170 => 100, 1400 => 1000. Values below 1 still use one local unit.
        if ($value < 1) {
            return 1;
        }
        $power = max(0, min(9, (int)floor(log10($value))));
        return 10 ** $power;
    }
}
