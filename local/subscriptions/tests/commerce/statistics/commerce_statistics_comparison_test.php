<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsComparison;

final class commerce_statistics_comparison_test extends \advanced_testcase {
    public function test_comparison_calculates_delta_percentage_and_trend(): void {
        $comparison = CommerceStatisticsComparison::compare(150, 100);
        $this->assertSame(50, $comparison->delta());
        $this->assertSame(50.0, $comparison->delta_percent());
        $this->assertSame(CommerceStatisticsComparison::TREND_UP, $comparison->trend());
    }

    public function test_zero_previous_value_has_no_percentage(): void {
        $comparison = CommerceStatisticsComparison::compare(10, 0);
        $this->assertSame(10, $comparison->delta());
        $this->assertNull($comparison->delta_percent());
        $this->assertSame(CommerceStatisticsComparison::TREND_UP, $comparison->trend());
    }

    public function test_missing_previous_period_is_explicit(): void {
        $comparison = CommerceStatisticsComparison::compare(10, null);
        $this->assertNull($comparison->delta());
        $this->assertSame(CommerceStatisticsComparison::TREND_NOT_AVAILABLE, $comparison->trend());
    }
}
