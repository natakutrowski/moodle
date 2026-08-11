<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Central limits protecting the CRM statistics screens from accidental oversized queries. */
final class CommerceStatisticsQueryPolicy {
    public const MAX_DASHBOARD_DAYS = 365;
    public const MAX_TOP_PRODUCTS = 20;

    public static function dashboard_days(int $days): int {
        return max(1, min(self::MAX_DASHBOARD_DAYS, $days));
    }

    public static function top_products_limit(int $limit): int {
        return max(1, min(self::MAX_TOP_PRODUCTS, $limit));
    }

    public static function cache_key(
        string $operation,
        CommerceStatisticsPeriod $period,
        CommerceStatisticsFilter $filter,
        array $extra = []
    ): string {
        $parts = [
            $operation,
            (string)$period->start(),
            (string)$period->end(),
            $filter->currency() ?? '*',
            $filter->provider() ?? '*',
            $filter->product_reference() ?? '*',
            ...array_map(static fn(mixed $value): string => (string)$value, $extra),
        ];

        return hash('sha256', implode('|', $parts));
    }
}
