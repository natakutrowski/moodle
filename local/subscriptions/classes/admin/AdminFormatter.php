<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminFormatter {

    public static function date(?int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }

        return date('d/m/y', $timestamp);
    }

    public static function datetime(?int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }

        return date('d/m/y H:i', $timestamp);
    }

    public static function subscription_end(?int $timestamp): string {
        if (empty($timestamp) || $timestamp > strtotime('2100-01-01')) {
            return get_string('unlimited', 'local_subscriptions');
        }

        return self::date($timestamp);
    }

    public static function price($amount, ?string $currency): string {
        $amount = (float)($amount ?? 0);

        if ($amount <= 0) {
            return '-';
        }

        return format_float($amount, 2) . ' ' . strtoupper((string)$currency);
    }

    public static function period(?int $start, ?int $end): string {
        return self::date($start) . ' → ' . self::subscription_end($end);
    }
}