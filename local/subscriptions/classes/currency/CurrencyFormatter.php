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
     * Format an integer minor-unit amount without introducing floating point.
     */
    public static function format_minor(
        int $amountminor,
        string $currency
    ): string {
        $currency = Currency::sanitize($currency);
        $number = self::format_minor_number($amountminor, $currency);
        $symbol = Currency::display_symbol($currency);

        if ($currency === '' || $symbol === '') {
            return $number;
        }

        return Currency::symbol_position($currency) === 'before'
            ? $symbol . "\u{00A0}" . $number
            : $number . "\u{00A0}" . $symbol;
    }

    /**
     * Format a minor-unit amount while preserving the ISO code presentation.
     *
     * This is useful for invoices, CRM and other financial/admin surfaces where
     * the explicit currency code is preferable to a possibly ambiguous symbol.
     */
    public static function format_minor_code(
        int $amountminor,
        string $currency
    ): string {
        $currency = Currency::sanitize($currency);
        $number = self::format_minor_number($amountminor, $currency);
        return $currency === '' ? $number : $number . "\u{00A0}" . $currency;
    }

    /** Format only the numeric major-unit part of an integer minor-unit amount. */
    public static function format_minor_number(int $amountminor, string $currency): string {
        $exponent = Currency::minor_unit_exponent($currency);
        $major = self::minor_to_major_string($amountminor, $exponent);
        return self::format_decimal_string($major, $exponent);
    }

    private static function minor_to_major_string(int $amountminor, int $exponent): string {
        if ($exponent === 0) {
            return (string)$amountminor;
        }
        $factor = 10 ** $exponent;
        return intdiv($amountminor, $factor) . '.' . str_pad(
            (string)($amountminor % $factor),
            $exponent,
            '0',
            STR_PAD_LEFT
        );
    }

    private static function format_decimal_string(string $amount, int $decimals): string {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $thousandssep = self::normalise_locale_separator(get_string('thousandssep', 'langconfig'));
        $formattedwhole = number_format((int)$whole, 0, '.', $thousandssep);
        if ($decimals === 0) {
            return $formattedwhole;
        }
        return $formattedwhole . get_string('decsep', 'langconfig') . str_pad($fraction, $decimals, '0');
    }

    /** Convert HTML-style langconfig separators into their actual text character. */
    private static function normalise_locale_separator(string $separator): string {
        return match ($separator) {
            '&nbsp;', '&#160;', '&#xA0;', '&#xa0;' => "\u{00A0}",
            default => html_entity_decode($separator, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        };
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