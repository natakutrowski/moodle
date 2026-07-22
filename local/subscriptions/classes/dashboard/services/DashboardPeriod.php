<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

/**
 * Supported CRM Dashboard periods.
 */
final class DashboardPeriod {

    public const TODAY = 'today';
    public const WEEK = 'week';
    public const MONTH = 'month';
    public const YEAR = 'year';
    public const ALL = 'all';

    /**
     * Normalizes one submitted Dashboard period.
     */
    public static function normalize(
        string $period
    ): string {
        return in_array(
            $period,
            self::allowed(),
            true
        )
            ? $period
            : self::TODAY;
    }

    /**
     * Returns all supported periods in display order.
     *
     * @return string[]
     */
    public static function allowed(): array {
        return [
            self::TODAY,
            self::WEEK,
            self::MONTH,
            self::YEAR,
            self::ALL,
        ];
    }

    /**
     * Returns the current range for one period.
     *
     * The end timestamp is exclusive.
     *
     * @return array{start: int, end: int}
     */
    public static function range(
        string $period
    ): array {
        $period = self::normalize($period);

        if ($period === self::WEEK) {
            return [
                'start' => strtotime(
                    'monday this week 00:00:00'
                ),
                'end' => strtotime(
                    'monday next week 00:00:00'
                ),
            ];
        }

        if ($period === self::MONTH) {
            return [
                'start' => strtotime(
                    'first day of this month 00:00:00'
                ),
                'end' => strtotime(
                    'first day of next month 00:00:00'
                ),
            ];
        }

        if ($period === self::YEAR) {
            return [
                'start' => strtotime(
                    'first day of january this year 00:00:00'
                ),
                'end' => strtotime(
                    'first day of january next year 00:00:00'
                ),
            ];
        }

        if ($period === self::ALL) {
            return [
                'start' => 0,
                'end' => time() + 1,
            ];
        }

        $start = strtotime('today');

        return [
            'start' => $start,
            'end' => $start + DAYSECS,
        ];
    }

    /**
     * Returns the period immediately preceding the selected period.
     *
     * For the all-time period, no meaningful preceding period exists.
     * An empty timestamp range is therefore returned.
     *
     * @return array{start: int, end: int}
     */
    public static function previous_range(
        string $period
    ): array {
        $period = self::normalize($period);

        if ($period === self::WEEK) {
            return [
                'start' => strtotime(
                    'monday last week 00:00:00'
                ),
                'end' => strtotime(
                    'monday this week 00:00:00'
                ),
            ];
        }

        if ($period === self::MONTH) {
            return [
                'start' => strtotime(
                    'first day of last month 00:00:00'
                ),
                'end' => strtotime(
                    'first day of this month 00:00:00'
                ),
            ];
        }

        if ($period === self::YEAR) {
            return [
                'start' => strtotime(
                    'first day of january last year 00:00:00'
                ),
                'end' => strtotime(
                    'first day of january this year 00:00:00'
                ),
            ];
        }

        if ($period === self::ALL) {
            return [
                'start' => 0,
                'end' => 0,
            ];
        }

        $end = strtotime('today');

        return [
            'start' => $end - DAYSECS,
            'end' => $end,
        ];
    }

    /**
     * Returns whether the period has a meaningful previous period.
     */
    public static function is_comparable(
        string $period
    ): bool {
        return self::normalize($period)
            !== self::ALL;
    }

    /**
     * Returns the duration of one resolved range.
     *
     * @param array{start: int, end: int} $range
     */
    public static function duration(
        array $range
    ): int {
        return max(
            0,
            (int)$range['end']
                - (int)$range['start']
        );
    }

    /**
     * Returns the translated period label.
     */
    public static function label(
        string $period
    ): string {
        return get_string(
            'dashboard_period_'
                . self::normalize($period),
            'local_subscriptions'
        );
    }
}