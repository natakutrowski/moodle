<?php

declare(strict_types=1);

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/** Shared customer-facing currency labels. */
final class CommerceCurrencyLabelFormatter {
    private const FLAGS = [
        'EUR' => '🇪🇺', 'USD' => '🇺🇸', 'GBP' => '🇬🇧', 'RUB' => '🇷🇺',
        'CHF' => '🇨🇭', 'JPY' => '🇯🇵', 'AMD' => '🇦🇲', 'CAD' => '🇨🇦',
        'AUD' => '🇦🇺',
    ];

    public static function format(string $currency): string {
        $code = strtoupper(trim($currency));
        $symbol = \local_subscriptions\subscription_config::get_currency_symbol($code);
        $label = $symbol !== null && $symbol !== '' ? $code . ' (' . $symbol . ')' : $code;
        return isset(self::FLAGS[$code]) ? self::FLAGS[$code] . ' ' . $label : $label;
    }
}
