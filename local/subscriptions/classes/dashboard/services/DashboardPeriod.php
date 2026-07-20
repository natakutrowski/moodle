<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

final class DashboardPeriod {

    public const TODAY = 'today';
    public const WEEK = 'week';
    public const MONTH = 'month';

    public static function normalize(string $period): string {
        return in_array($period, self::allowed(), true) ? $period : self::TODAY;
    }

    public static function allowed(): array {
        return [
            self::TODAY,
            self::WEEK,
            self::MONTH,
        ];
    }

    public static function range(string $period): array {
        $period = self::normalize($period);

        if ($period === self::WEEK) {
            return [
                'start' => strtotime('monday this week'),
                'end' => strtotime('monday next week'),
            ];
        }

        if ($period === self::MONTH) {
            return [
                'start' => strtotime(date('Y-m-01 00:00:00')),
                'end' => strtotime(date('Y-m-01 00:00:00', strtotime('first day of next month'))),
            ];
        }

        $start = strtotime('today');

        return [
            'start' => $start,
            'end' => $start + DAYSECS,
        ];
    }

    /**
     * Return the period immediately preceding the selected period.
     *
     * The previous period follows the same calendar semantics:
     *
     * - today: previous calendar day;
     * - week: previous Monday-to-Monday week;
     * - month: previous calendar month.
     *
     * @param string $period
     * @return array{start: int, end: int}
     */
    public static function previous_range(
        string $period
    ): array {
        $period = self::normalize($period);

        if ($period === self::WEEK) {
            return [
                'start' => strtotime('monday last week'),
                'end' => strtotime('monday this week'),
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

        $end = strtotime('today');

        return [
            'start' => $end - DAYSECS,
            'end' => $end,
        ];
    }

    /**
     * Return the duration of one resolved range.
     *
     * @param array{start: int, end: int} $range
     * @return int
     */
    public static function duration(array $range): int {
        return max(
            0,
            (int)$range['end'] - (int)$range['start']
        );
    }

    public static function label(string $period): string {
        return get_string('dashboard_period_' . self::normalize($period), 'local_subscriptions');
    }
}