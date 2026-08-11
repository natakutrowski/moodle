<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;
use local_subscriptions\commerce\statistics\CommerceStatisticsMetric;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsSnapshot;

final class commerce_statistics_snapshot_test extends \advanced_testcase {
    public function test_snapshot_keeps_currencies_separate(): void {
        $period = CommerceStatisticsPeriod::custom(1000, 2000);
        $snapshot = new CommerceStatisticsSnapshot($period, $period->previous());
        $snapshot->add(new CommerceStatisticsMetric('paid_minor', CommerceStatisticsComparison::compare(12000, 10000), 'EUR'));
        $snapshot->add(new CommerceStatisticsMetric('paid_minor', CommerceStatisticsComparison::compare(900000, 800000), 'RUB'));
        $this->assertSame(12000, $snapshot->get('paid_minor', 'EUR')->comparison()->current());
        $this->assertSame(900000, $snapshot->get('paid_minor', 'RUB')->comparison()->current());
        $this->assertCount(2, $snapshot->metrics());
    }

    public function test_duplicate_metric_is_rejected(): void {
        $period = CommerceStatisticsPeriod::custom(1000, 2000);
        $snapshot = new CommerceStatisticsSnapshot($period, $period->previous());
        $metric = new CommerceStatisticsMetric('orders', CommerceStatisticsComparison::compare(1, 0), 'EUR');
        $snapshot->add($metric);
        $this->expectException(\coding_exception::class);
        $snapshot->add($metric);
    }
}
