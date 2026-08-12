<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Reusable resolver for admin Commerce statistics periods. */
final class CommerceStatisticsPeriodResolver {
    /** @return array<string,string> */
    public static function options(): array {
        return [
            'today' => get_string('commerce_statistics_period_today', 'local_subscriptions'),
            '7' => get_string('commerce_statistics_period_7_days', 'local_subscriptions'),
            '30' => get_string('commerce_statistics_period_30_days', 'local_subscriptions'),
            '90' => get_string('commerce_statistics_period_90_days', 'local_subscriptions'),
            '180' => get_string('commerce_statistics_period_180_days', 'local_subscriptions'),
            '365' => get_string('commerce_statistics_period_365_days', 'local_subscriptions'),
            'all' => get_string('commerce_statistics_period_all_time', 'local_subscriptions'),
            'custom' => get_string('commerce_statistics_period_custom', 'local_subscriptions'),
        ];
    }

    public static function resolve(string $key, string $from = '', string $until = '', ?int $now = null): CommerceStatisticsPeriod {
        $now ??= time();
        $key = strtolower(trim($key));
        if ($key === 'today') {
            return CommerceStatisticsPeriod::custom(usergetmidnight($now), $now + 1);
        }
        if ($key === 'all') {
            return CommerceStatisticsPeriod::custom(1, $now + 1);
        }
        if ($key === 'custom') {
            $start = self::date_start($from);
            $endstart = self::date_start($until);
            if ($start !== null && $endstart !== null && $endstart >= $start) {
                return CommerceStatisticsPeriod::custom($start, $endstart + DAYSECS);
            }
            return CommerceStatisticsPeriod::last_days(30, $now);
        }
        $days = in_array($key, ['7', '30', '90', '180', '365'], true) ? (int)$key : 30;
        // Calendar-day periods are easier to read on charts than rolling 24-hour windows.
        $start = usergetmidnight($now - (($days - 1) * DAYSECS));
        return CommerceStatisticsPeriod::custom($start, $now + 1);
    }

    /**
     * Returns the immediately preceding comparison period with the same effective duration.
     *
     * Calendar presets are shifted by their complete calendar span so "Today" compares
     * with the same elapsed hours yesterday and "7 days" compares with the corresponding
     * seven-day window immediately before it. All-time has no meaningful predecessor.
     */
    public static function previous(
        string $key,
        CommerceStatisticsPeriod $period
    ): ?CommerceStatisticsPeriod {
        $key = strtolower(trim($key));
        if ($key === 'all') {
            return null;
        }

        if ($key === 'today') {
            $shift = DAYSECS;
        } else if (in_array($key, ['7', '30', '90', '180', '365'], true)) {
            $shift = ((int)$key) * DAYSECS;
        } else {
            $shift = $period->duration();
        }

        $start = $period->start() - $shift;
        $end = $period->end() - $shift;
        if ($start < 1 || $end <= $start) {
            return null;
        }

        return CommerceStatisticsPeriod::custom($start, $end);
    }

    public static function granularity(CommerceStatisticsPeriod $period): string {
        $hours = $period->duration() / HOURSECS;
        if ($hours <= 48) { return 'hour'; }
        $days = (int)ceil($period->duration() / DAYSECS);
        return $days <= 45 ? 'day' : ($days <= 180 ? 'week' : 'month');
    }

    private static function date_start(string $value): ?int {
        if (!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', trim($value), $m)) { return null; }
        $year=(int)$m[1]; $month=(int)$m[2]; $day=(int)$m[3];
        if (!checkdate($month,$day,$year)) { return null; }
        return make_timestamp($year,$month,$day,0,0,0);
    }
}
