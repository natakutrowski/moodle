<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Application service building currency-safe Commerce statistics snapshots. */
final class CommerceStatisticsService {
    private readonly CommerceStatisticsRequestCache $cache;

    public function __construct(
        private readonly CommerceStatisticsRepository $repository,
        ?CommerceStatisticsRequestCache $cache = null
    ) {
        $this->cache = $cache ?? new CommerceStatisticsRequestCache();
    }

    public function snapshot(
        CommerceStatisticsPeriod $period,
        ?CommerceStatisticsFilter $filter = null
    ): CommerceStatisticsSnapshot {
        $filter ??= CommerceStatisticsFilter::all();
        $previousperiod = $period->previous();

        $current = $this->aggregate($period, $filter);
        $previous = $this->aggregate($previousperiod, $filter);
        $snapshot = new CommerceStatisticsSnapshot($period, $previousperiod);
        $currencies = array_values(array_unique(array_merge(array_keys($current), array_keys($previous))));
        sort($currencies);

        foreach ($currencies as $currency) {
            $currentvalues = $current[$currency] ?? [];
            $previousvalues = $previous[$currency] ?? [];

            foreach ($this->metric_keys() as $key) {
                $snapshot->add(new CommerceStatisticsMetric(
                    $key,
                    CommerceStatisticsComparison::compare(
                        $currentvalues[$key] ?? 0,
                        $previousvalues[$key] ?? 0
                    ),
                    $currency
                ));
            }

            $orders = (int)($currentvalues['orders'] ?? 0);
            $previousorders = (int)($previousvalues['orders'] ?? 0);

            $snapshot->add(new CommerceStatisticsMetric(
                'average_order_minor',
                CommerceStatisticsComparison::compare(
                    $orders > 0 ? ((int)($currentvalues['paid_minor'] ?? 0) / $orders) : 0,
                    $previousorders > 0
                        ? ((int)($previousvalues['paid_minor'] ?? 0) / $previousorders)
                        : 0
                ),
                $currency
            ));

            $snapshot->add(new CommerceStatisticsMetric(
                'net_paid_minor',
                CommerceStatisticsComparison::compare(
                    (int)($currentvalues['paid_minor'] ?? 0)
                        - (int)($currentvalues['refunded_minor'] ?? 0),
                    (int)($previousvalues['paid_minor'] ?? 0)
                        - (int)($previousvalues['refunded_minor'] ?? 0)
                ),
                $currency
            ));
        }

        return $snapshot;
    }

    private function aggregate(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter): array {
        $key = CommerceStatisticsQueryPolicy::cache_key('aggregate', $period, $filter);

        return $this->cache->remember(
            $key,
            fn(): array => $this->repository->aggregate($period, $filter)
        );
    }

    private function metric_keys(): array {
        return [
            'orders',
            'customers',
            'ordered_minor',
            'paid_minor',
            'successful_payments',
            'failed_payments',
            'refunded_payments',
            'refunded_minor',
            'pending_fulfillments',
        ];
    }
}
