<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\trends\DashboardTrendsService;

/**
 * Tests for Dashboard CRM trends service.
 *
 * @covers \local_subscriptions\dashboard\trends\DashboardTrendsService
 */
final class DashboardTrendsServiceTest extends advanced_testcase {

    public function test_service_uses_dashboard_periods(): void {
        $this->resetAfterTest(true);

        $comparison = (
            new DashboardTrendsService()
        )->load(
            DashboardPeriod::TODAY
        );

        $current =
            DashboardPeriod::range(
                DashboardPeriod::TODAY
            );

        $previous =
            DashboardPeriod::previous_range(
                DashboardPeriod::TODAY
            );

        $this->assertSame(
            $current['start'],
            $comparison->current->start
        );

        $this->assertSame(
            $current['end'],
            $comparison->current->end
        );

        $this->assertSame(
            $previous['start'],
            $comparison->previous->start
        );

        $this->assertSame(
            $previous['end'],
            $comparison->previous->end
        );
    }

    public function test_invalid_period_falls_back_to_today(): void {
        $this->resetAfterTest(true);

        $comparison = (
            new DashboardTrendsService()
        )->load('invalid-period');

        $today =
            DashboardPeriod::range(
                DashboardPeriod::TODAY
            );

        $this->assertSame(
            $today['start'],
            $comparison->current->start
        );

        $this->assertSame(
            $today['end'],
            $comparison->current->end
        );
    }
}