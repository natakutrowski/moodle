<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;

final class commerce_statistics_series_test extends \advanced_testcase {
    public function test_granularity_contract(): void {
        $this->assertSame('day', CommerceStatisticsRepository::granularity(CommerceStatisticsPeriod::custom(100000, 100000 + 30 * DAYSECS)));
        $this->assertSame('week', CommerceStatisticsRepository::granularity(CommerceStatisticsPeriod::custom(100000, 100000 + 90 * DAYSECS)));
        $this->assertSame('month', CommerceStatisticsRepository::granularity(CommerceStatisticsPeriod::custom(100000, 100000 + 365 * DAYSECS)));
    }
    public function test_series_exposes_chart_ready_values(): void {
        $series = new CommerceStatisticsSeries('revenue', 'EUR', 'day', [['timestamp' => 1, 'label' => '1 Jan', 'value' => 1200]]);
        $this->assertSame(['1 Jan'], $series->labels());
        $this->assertSame([1200], $series->values());
        $this->assertSame('EUR', $series->currency());
    }
}
