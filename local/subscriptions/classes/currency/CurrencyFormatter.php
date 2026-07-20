<?php

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Locale-aware CRM currency formatter.
 */
final class CurrencyFormatter {

    /**
     * Format an amount with a safe currency presentation.
     */
    public static function format(
        float $amount,
        string $currency
    ): string {
        $currency = Currency::sanitize($currency);

        if ($currency === '') {
            return self::format_number($amount, 2);
        }

        $decimals = Currency::decimals($currency);
        $number = self::format_number($amount, $decimals);
        $symbol = Currency::display_symbol($currency);

        if (Currency::symbol_position($currency) === 'before') {
            return $symbol . "\u{00A0}" . $number;
        }

        return $number . "\u{00A0}" . $symbol;
    }

    /**
     * Format only the numeric part using the current Moodle language.
     */
    public static function format_number(
        float $amount,
        int $decimals
    ): string {
        $decimals = max(0, min(4, $decimals));

        return format_float(
            $amount,
            $decimals,
            true,
            true
        );
    }
}