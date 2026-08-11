<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsQueryPolicy;
use local_subscriptions\commerce\statistics\CommerceStatisticsRequestCache;

final class commerce_statistics_cache_policy_test extends \advanced_testcase {
    public function test_request_cache_executes_loader_once(): void {
        $cache = new CommerceStatisticsRequestCache();
        $calls = 0;

        $first = $cache->remember('same-key', static function() use (&$calls): array {
            $calls++;
            return ['value' => 42];
        });
        $second = $cache->remember('same-key', static function() use (&$calls): array {
            $calls++;
            return ['value' => 99];
        });

        $this->assertSame(['value' => 42], $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
        $this->assertSame(1, $cache->count());
    }

    public function test_query_policy_bounds_dashboard_and_rankings(): void {
        $this->assertSame(1, CommerceStatisticsQueryPolicy::dashboard_days(0));
        $this->assertSame(365, CommerceStatisticsQueryPolicy::dashboard_days(9999));
        $this->assertSame(1, CommerceStatisticsQueryPolicy::top_products_limit(-4));
        $this->assertSame(20, CommerceStatisticsQueryPolicy::top_products_limit(999));
    }

    public function test_cache_key_separates_currency_provider_and_product(): void {
        $period = CommerceStatisticsPeriod::custom(1000, 2000);
        $eur = new CommerceStatisticsFilter('EUR', 'stripe', 'sku-a');
        $rub = new CommerceStatisticsFilter('RUB', 'stripe', 'sku-a');

        $this->assertNotSame(
            CommerceStatisticsQueryPolicy::cache_key('aggregate', $period, $eur),
            CommerceStatisticsQueryPolicy::cache_key('aggregate', $period, $rub)
        );
    }
}
