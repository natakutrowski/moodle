<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Builds chart-ready series and product rankings from Native Commerce data. */
final class CommerceStatisticsSeriesService {
    private readonly CommerceStatisticsRequestCache $cache;

    public function __construct(
        private readonly CommerceStatisticsRepository $repository,
        ?CommerceStatisticsRequestCache $cache = null
    ) {
        $this->cache = $cache ?? new CommerceStatisticsRequestCache();
    }

    public function revenue(
        CommerceStatisticsPeriod $period,
        ?CommerceStatisticsFilter $filter = null
    ): array {
        return $this->series('revenue', $period, $filter ?? CommerceStatisticsFilter::all());
    }

    public function orders(
        CommerceStatisticsPeriod $period,
        ?CommerceStatisticsFilter $filter = null
    ): array {
        return $this->series('orders', $period, $filter ?? CommerceStatisticsFilter::all());
    }

    public function payment_health(
        CommerceStatisticsPeriod $period,
        ?CommerceStatisticsFilter $filter = null
    ): array {
        $filter ??= CommerceStatisticsFilter::all();
        $key = CommerceStatisticsQueryPolicy::cache_key('payment_health', $period, $filter);

        return $this->cache->remember(
            $key,
            fn(): array => $this->repository->payment_health($period, $filter)
        );
    }

    public function top_products(
        CommerceStatisticsPeriod $period,
        ?CommerceStatisticsFilter $filter = null,
        int $limit = 8
    ): array {
        $filter ??= CommerceStatisticsFilter::all();
        $limit = CommerceStatisticsQueryPolicy::top_products_limit($limit);
        $key = CommerceStatisticsQueryPolicy::cache_key('top_products', $period, $filter, [$limit]);

        return $this->cache->remember(
            $key,
            fn(): array => $this->repository->top_products($period, $filter, $limit)
        );
    }

    /** @param string|string[] $reference */
    public function product(
        CommerceStatisticsPeriod $period,
        string|array $reference,
        ?string $currency = null
    ): CommerceProductPerformance {
        $references = is_array($reference) ? $reference : [$reference];
        $references = array_values(array_unique(array_filter(array_map('trim', $references))));
        $primaryreference = $references[0] ?? '';
        $rows = $this->repository->product_statistics_for_references($period, $references, 100, $currency);
        $bycurrency = [];
        foreach ($rows as $row) {
            $bycurrency[$row->currency] = [
                'orders' => $row->orders,
                'paid_minor' => $row->revenueminor,
                'successful_payments' => $row->paidorders,
            ];
        }
        $series = $this->repository->product_revenue_series_for_references($period, $references, $currency);
        return new CommerceProductPerformance($primaryreference, $bycurrency, $series);
    }

    private function series(
        string $metric,
        CommerceStatisticsPeriod $period,
        CommerceStatisticsFilter $filter
    ): array {
        $key = CommerceStatisticsQueryPolicy::cache_key('series:' . $metric, $period, $filter);

        return $this->cache->remember(
            $key,
            fn(): array => $this->repository->time_series($period, $filter, $metric)
        );
    }
}
