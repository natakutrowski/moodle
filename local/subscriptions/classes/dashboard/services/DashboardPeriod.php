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

    public static function label(string $period): string {
        return get_string('dashboard_period_' . self::normalize($period), 'local_subscriptions');
    }
}