<?php

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Currency normalization and metadata.
 *
 * The CRM accepts any valid three-letter ISO-style currency code.
 * The metadata map only improves presentation for common currencies.
 */
final class Currency {

    /**
     * Known presentation metadata.
     *
     * position:
     * - before: symbol before amount;
     * - after: symbol after amount.
     *
     * @var array<string, array{
     *     symbol: string,
     *     decimals: int,
     *     position: string,
     *     ambiguous: bool
     * }>
     */
    private const METADATA = [
        'EUR' => [
            'symbol' => '€',
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
        ],
        'RUB' => [
            'symbol' => '₽',
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
        ],
        'USD' => [
            'symbol' => '$',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'GBP' => [
            'symbol' => '£',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => false,
        ],
        'CHF' => [
            'symbol' => 'CHF',
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
        ],
        'CAD' => [
            'symbol' => '$',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'AUD' => [
            'symbol' => '$',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'NZD' => [
            'symbol' => '$',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'JPY' => [
            'symbol' => '¥',
            'decimals' => 0,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'CNY' => [
            'symbol' => '¥',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => true,
        ],
        'KRW' => [
            'symbol' => '₩',
            'decimals' => 0,
            'position' => 'before',
            'ambiguous' => false,
        ],
        'INR' => [
            'symbol' => '₹',
            'decimals' => 2,
            'position' => 'before',
            'ambiguous' => false,
        ],
        'AED' => [
            'symbol' => 'AED',
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
        ],
    ];

    /**
     * Normalize a currency code.
     */
    public static function normalize(?string $currency): string {
        return strtoupper(trim((string)$currency));
    }

    /**
     * Whether a value looks like a supported ISO-style currency code.
     *
     * Unknown valid codes are accepted and displayed using their ISO code.
     */
    public static function is_valid(?string $currency): bool {
        return preg_match(
            '/^[A-Z]{3}$/',
            self::normalize($currency)
        ) === 1;
    }

    /**
     * Return a normalized currency or an empty string.
     */
    public static function sanitize(?string $currency): string {
        $currency = self::normalize($currency);

        return self::is_valid($currency)
            ? $currency
            : '';
    }

    /**
     * Display symbol or ISO code.
     *
     * Ambiguous symbols use the ISO code to prevent confusion between
     * currencies such as USD, CAD and AUD.
     */
    public static function display_symbol(string $currency): string {
        $currency = self::sanitize($currency);

        if ($currency === '') {
            return '';
        }

        $metadata = self::metadata($currency);

        if ($metadata['ambiguous']) {
            return $currency;
        }

        return $metadata['symbol'];
    }

    /**
     * Number of display decimals.
     */
    public static function decimals(string $currency): int {
        return self::metadata($currency)['decimals'];
    }

    /**
     * Preferred symbol position.
     */
    public static function symbol_position(string $currency): string {
        return self::metadata($currency)['position'];
    }

    /**
     * Return normalized metadata with a generic fallback.
     *
     * @return array{
     *     symbol: string,
     *     decimals: int,
     *     position: string,
     *     ambiguous: bool
     * }
     */
    private static function metadata(string $currency): array {
        $currency = self::sanitize($currency);

        if (
            $currency !== ''
            && array_key_exists($currency, self::METADATA)
        ) {
            return self::METADATA[$currency];
        }

        return [
            'symbol' => $currency,
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
        ];
    }
}