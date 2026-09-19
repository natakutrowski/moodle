<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Exact conversions used at Commerce form/API boundaries. */
final class CommerceCurrencyAmount {
    public static function from_major_input(string $amount, string $currency): CommerceMoney {
        $amount = trim(str_replace(',', '.', $amount));
        return CommerceMoney::from_major_for_currency($amount, $currency);
    }

    public static function major_input_from_minor(int $amountminor, string $currency): string {
        return CommerceMoney::from_minor($amountminor, $currency)->get_amount_major_for_currency();
    }

    /**
     * Return a major-unit float only for compatibility boundaries that still expose floats.
     * Native Commerce calculations must continue to use integer minor units.
     */
    public static function major_float_from_minor(int $amountminor, string $currency): float {
        return (float)self::major_input_from_minor($amountminor, $currency);
    }
}
