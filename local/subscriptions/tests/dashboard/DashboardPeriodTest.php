<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Tests for Dashboard period ranges.
 *
 * @covers \local_subscriptions\dashboard\services\DashboardPeriod
 */
final class DashboardPeriodTest extends advanced_testcase {

    public function test_today_previous_range_ends_at_today_start(): void {
        $current = DashboardPeriod::range(
            DashboardPeriod::TODAY
        );

        $previous = DashboardPeriod::previous_range(
            DashboardPeriod::TODAY
        );

        $this->assertSame(
            $current['start'],
            $previous['end']
        );

        $this->assertSame(
            DAYSECS,
            DashboardPeriod::duration($previous)
        );
    }

    public function test_week_previous_range_ends_at_current_start(): void {
        $current = DashboardPeriod::range(
            DashboardPeriod::WEEK
        );

        $previous = DashboardPeriod::previous_range(
            DashboardPeriod::WEEK
        );

        $this->assertSame(
            $current['start'],
            $previous['end']
        );

        $this->assertSame(
            7 * DAYSECS,
            DashboardPeriod::duration($previous)
        );
    }

    public function test_month_previous_range_ends_at_current_start(): void {
        $current = DashboardPeriod::range(
            DashboardPeriod::MONTH
        );

        $previous = DashboardPeriod::previous_range(
            DashboardPeriod::MONTH
        );

        $this->assertSame(
            $current['start'],
            $previous['end']
        );

        $this->assertGreaterThanOrEqual(
            28 * DAYSECS,
            DashboardPeriod::duration($previous)
        );

        $this->assertLessThanOrEqual(
            31 * DAYSECS,
            DashboardPeriod::duration($previous)
        );
    }

    public function test_invalid_period_is_normalized_to_today(): void {
        $this->assertSame(
            DashboardPeriod::range(
                DashboardPeriod::TODAY
            ),
            DashboardPeriod::range(
                'unknown-period'
            )
        );

        $this->assertSame(
            DashboardPeriod::previous_range(
                DashboardPeriod::TODAY
            ),
            DashboardPeriod::previous_range(
                'unknown-period'
            )
        );
    }
}