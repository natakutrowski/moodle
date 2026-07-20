<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\trends\DashboardTrendComparison;
use local_subscriptions\dashboard\trends\DashboardTrendMetric;

/**
 * Tests for CRM trend comparisons.
 *
 * @covers \local_subscriptions\dashboard\trends\DashboardTrendComparison
 */
final class DashboardTrendComparisonTest extends advanced_testcase {

    public function test_positive_metric_increase_is_improvement(): void {
        $comparison = new DashboardTrendComparison(
            new DashboardTrendMetric(
                'engagement_up',
                10,
                DashboardTrendMetric::
                    DIRECTION_IMPROVING,
                DashboardTrendMetric::
                    SEVERITY_POSITIVE
            ),
            new DashboardTrendMetric(
                'engagement_up',
                5,
                DashboardTrendMetric::
                    DIRECTION_IMPROVING,
                DashboardTrendMetric::
                    SEVERITY_POSITIVE
            )
        );

        $this->assertSame(
            5,
            $comparison->difference()
        );

        $this->assertSame(
            100.0,
            $comparison->variation()
        );

        $this->assertTrue(
            $comparison->business_is_improving()
        );

        $this->assertFalse(
            $comparison->business_is_degrading()
        );
    }

    public function test_negative_metric_increase_is_degradation(): void {
        $comparison = new DashboardTrendComparison(
            new DashboardTrendMetric(
                'risk_up',
                8,
                DashboardTrendMetric::
                    DIRECTION_DEGRADING,
                DashboardTrendMetric::
                    SEVERITY_CRITICAL
            ),
            new DashboardTrendMetric(
                'risk_up',
                4,
                DashboardTrendMetric::
                    DIRECTION_DEGRADING,
                DashboardTrendMetric::
                    SEVERITY_CRITICAL
            )
        );

        $this->assertSame(
            4,
            $comparison->difference()
        );

        $this->assertTrue(
            $comparison->business_is_degrading()
        );

        $this->assertFalse(
            $comparison->business_is_improving()
        );
    }

    public function test_negative_metric_decrease_is_improvement(): void {
        $comparison = new DashboardTrendComparison(
            new DashboardTrendMetric(
                'engagement_down',
                2,
                DashboardTrendMetric::
                    DIRECTION_DEGRADING,
                DashboardTrendMetric::
                    SEVERITY_WARNING
            ),
            new DashboardTrendMetric(
                'engagement_down',
                6,
                DashboardTrendMetric::
                    DIRECTION_DEGRADING,
                DashboardTrendMetric::
                    SEVERITY_WARNING
            )
        );

        $this->assertSame(
            -4,
            $comparison->difference()
        );

        $this->assertTrue(
            $comparison->business_is_improving()
        );
    }

    public function test_zero_previous_returns_null_variation(): void {
        $comparison = new DashboardTrendComparison(
            new DashboardTrendMetric(
                'global_up',
                5,
                DashboardTrendMetric::
                    DIRECTION_IMPROVING,
                DashboardTrendMetric::
                    SEVERITY_POSITIVE
            ),
            new DashboardTrendMetric(
                'global_up',
                0,
                DashboardTrendMetric::
                    DIRECTION_IMPROVING,
                DashboardTrendMetric::
                    SEVERITY_POSITIVE
            )
        );

        $this->assertNull(
            $comparison->variation()
        );
    }
}