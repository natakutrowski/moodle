<?php

declare(strict_types=1);

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/** Shared customer-facing currency labels. */
final class CommerceCurrencyLabelFormatter {
    public static function format(string $currency): string {
        $code = Currency::sanitize($currency);
        if ($code === '') {
            return '';
        }

        $marker = Currency::visual_marker($code);
        $symbol = Currency::symbol($code);
        $label = $symbol !== '' ? $code . ' (' . $symbol . ')' : $code;

        return ($marker !== '' ? $marker . ' ' : '') . $label;
    }
}
