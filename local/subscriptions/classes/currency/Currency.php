<?php

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Currency normalization and centralized ISO-style presentation metadata.
 *
 * This registry describes currencies the CampusFR monetary engine understands.
 * It does not mean that a currency is enabled in Commerce, priced on a product,
 * or supported by a particular payment provider.
 */
final class Currency {

    /** @var array<string, array{symbol:string, decimals:int, position:string, ambiguous:bool, name:string}> */
    private const METADATA = [
        'EUR' => ['symbol' => '€', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Euro'],
        'RUB' => ['symbol' => '₽', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Russian ruble'],
        'BYN' => ['symbol' => 'Br', 'decimals' => 2, 'position' => 'after', 'ambiguous' => true, 'name' => 'Belarusian ruble'],
        'USD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'US dollar'],
        'CAD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Canadian dollar'],
        'MXN' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Mexican peso'],
        'BRL' => ['symbol' => 'R$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => false, 'name' => 'Brazilian real'],
        'COP' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Colombian peso'],
        'CLP' => ['symbol' => '$', 'decimals' => 0, 'position' => 'before', 'ambiguous' => true, 'name' => 'Chilean peso'],
        'ARS' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Argentine peso'],
        'GBP' => ['symbol' => '£', 'decimals' => 2, 'position' => 'before', 'ambiguous' => false, 'name' => 'Pound sterling'],
        'CHF' => ['symbol' => 'CHF', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Swiss franc'],
        'PLN' => ['symbol' => 'zł', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Polish złoty'],
        'SEK' => ['symbol' => 'kr', 'decimals' => 2, 'position' => 'after', 'ambiguous' => true, 'name' => 'Swedish krona'],
        'NOK' => ['symbol' => 'kr', 'decimals' => 2, 'position' => 'after', 'ambiguous' => true, 'name' => 'Norwegian krone'],
        'DKK' => ['symbol' => 'kr', 'decimals' => 2, 'position' => 'after', 'ambiguous' => true, 'name' => 'Danish krone'],
        'CZK' => ['symbol' => 'Kč', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Czech koruna'],
        'HUF' => ['symbol' => 'Ft', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Hungarian forint'],
        'RON' => ['symbol' => 'lei', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Romanian leu'],
        'JPY' => ['symbol' => '¥', 'decimals' => 0, 'position' => 'before', 'ambiguous' => true, 'name' => 'Japanese yen'],
        'KRW' => ['symbol' => '₩', 'decimals' => 0, 'position' => 'before', 'ambiguous' => false, 'name' => 'South Korean won'],
        'CNY' => ['symbol' => '¥', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Chinese yuan'],
        'HKD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Hong Kong dollar'],
        'SGD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Singapore dollar'],
        'INR' => ['symbol' => '₹', 'decimals' => 2, 'position' => 'before', 'ambiguous' => false, 'name' => 'Indian rupee'],
        'AUD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'Australian dollar'],
        'NZD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'New Zealand dollar'],
        'AED' => ['symbol' => 'AED', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'UAE dirham'],
        'SAR' => ['symbol' => 'SAR', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Saudi riyal'],
        'ZAR' => ['symbol' => 'R', 'decimals' => 2, 'position' => 'before', 'ambiguous' => true, 'name' => 'South African rand'],
        'MAD' => ['symbol' => 'MAD', 'decimals' => 2, 'position' => 'after', 'ambiguous' => false, 'name' => 'Moroccan dirham'],
        'XOF' => ['symbol' => 'CFA', 'decimals' => 0, 'position' => 'after', 'ambiguous' => true, 'name' => 'West African CFA franc'],
        'XAF' => ['symbol' => 'FCFA', 'decimals' => 0, 'position' => 'after', 'ambiguous' => true, 'name' => 'Central African CFA franc'],
        'TND' => ['symbol' => 'د.ت', 'decimals' => 3, 'position' => 'after', 'ambiguous' => false, 'name' => 'Tunisian dinar'],
    ];

    public static function normalize(?string $currency): string {
        return strtoupper(trim((string)$currency));
    }

    public static function is_valid(?string $currency): bool {
        return preg_match('/^[A-Z]{3}$/', self::normalize($currency)) === 1;
    }

    public static function sanitize(?string $currency): string {
        $currency = self::normalize($currency);
        return self::is_valid($currency) ? $currency : '';
    }

    /** @return string[] Currencies with explicit CampusFR metadata. */
    public static function known_codes(): array {
        return array_keys(self::METADATA);
    }

    public static function is_known(string $currency): bool {
        return array_key_exists(self::sanitize($currency), self::METADATA);
    }

    /** Raw presentation symbol, even when it is shared by several currencies. */
    public static function symbol(string $currency): string {
        $currency = self::sanitize($currency);
        return $currency === '' ? '' : self::metadata($currency)['symbol'];
    }

    public static function display_symbol(string $currency): string {
        $currency = self::sanitize($currency);
        if ($currency === '') {
            return '';
        }
        $metadata = self::metadata($currency);
        return $metadata['ambiguous'] ? $currency : $metadata['symbol'];
    }

    /** ISO minor-unit exponent used by the Commerce monetary domain. */
    public static function minor_unit_exponent(string $currency): int {
        return self::metadata($currency)['decimals'];
    }

    /** Backwards-compatible presentation alias. */
    public static function decimals(string $currency): int {
        return self::minor_unit_exponent($currency);
    }

    public static function symbol_position(string $currency): string {
        return self::metadata($currency)['position'];
    }

    public static function name(string $currency): string {
        return self::metadata($currency)['name'];
    }

    /**
     * Optional visual cue for administration/selectors only.
     *
     * This marker is deliberately presentation-only: it must never be used to
     * infer a market, customer country, payment provider, or payment method.
     */
    public static function visual_marker(string $currency): string {
        $markers = [
            'EUR' => '🇪🇺', 'RUB' => '🇷🇺', 'BYN' => '🇧🇾', 'USD' => '🇺🇸',
            'CAD' => '🇨🇦', 'MXN' => '🇲🇽', 'BRL' => '🇧🇷', 'COP' => '🇨🇴',
            'CLP' => '🇨🇱', 'ARS' => '🇦🇷', 'GBP' => '🇬🇧', 'CHF' => '🇨🇭',
            'PLN' => '🇵🇱', 'SEK' => '🇸🇪', 'NOK' => '🇳🇴', 'DKK' => '🇩🇰',
            'CZK' => '🇨🇿', 'HUF' => '🇭🇺', 'RON' => '🇷🇴', 'JPY' => '🇯🇵',
            'KRW' => '🇰🇷', 'CNY' => '🇨🇳', 'HKD' => '🇭🇰', 'SGD' => '🇸🇬',
            'INR' => '🇮🇳', 'AUD' => '🇦🇺', 'NZD' => '🇳🇿', 'AED' => '🇦🇪',
            'SAR' => '🇸🇦', 'ZAR' => '🇿🇦', 'MAD' => '🇲🇦', 'XOF' => '🌍',
            'XAF' => '🌍', 'TND' => '🇹🇳',
        ];
        $currency = self::sanitize($currency);
        return $markers[$currency] ?? '';
    }

    /** @return array{symbol:string, decimals:int, position:string, ambiguous:bool, name:string} */
    private static function metadata(string $currency): array {
        $currency = self::sanitize($currency);
        if ($currency !== '' && array_key_exists($currency, self::METADATA)) {
            return self::METADATA[$currency];
        }
        return [
            'symbol' => $currency,
            'decimals' => 2,
            'position' => 'after',
            'ambiguous' => false,
            'name' => $currency,
        ];
    }
}
