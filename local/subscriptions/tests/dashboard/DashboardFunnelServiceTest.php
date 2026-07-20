<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\funnel\DashboardFunnelService;
use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Integration tests for the Dashboard Funnel service.
 *
 * @covers \local_subscriptions\dashboard\funnel\DashboardFunnelService
 */
final class DashboardFunnelServiceTest extends advanced_testcase {

    public function test_service_returns_current_and_previous_periods(): void {
        $this->resetAfterTest(true);

        $comparison = (
            new DashboardFunnelService()
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

        $this->assertSame(
            30,
            $comparison->conversionwindowdays
        );
    }

    public function test_invalid_period_falls_back_to_today(): void {
        $this->resetAfterTest(true);

        $comparison = (
            new DashboardFunnelService()
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