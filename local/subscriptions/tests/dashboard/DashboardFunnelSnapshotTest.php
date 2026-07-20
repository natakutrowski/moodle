<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\funnel\DashboardFunnelSnapshot;

/**
 * Tests for Dashboard Funnel snapshot calculations.
 *
 * @covers \local_subscriptions\dashboard\funnel\DashboardFunnelSnapshot
 */
final class DashboardFunnelSnapshotTest extends advanced_testcase {

    public function test_ratios_are_calculated(): void {
        $snapshot = new DashboardFunnelSnapshot(
            100,
            200,
            20,
            10,
            4,
            8,
            3,
            2,
            5,
            2
        );

        $this->assertSame(
            50.0,
            $snapshot->user_trial_ratio()
        );

        $this->assertSame(
            40.0,
            $snapshot->observed_trial_conversion()
        );

        $this->assertSame(
            37.5,
            $snapshot->mature_trial_conversion()
        );
    }

    public function test_empty_denominators_return_null(): void {
        $snapshot = new DashboardFunnelSnapshot(
            100,
            200,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0
        );

        $this->assertNull(
            $snapshot->user_trial_ratio()
        );

        $this->assertNull(
            $snapshot->observed_trial_conversion()
        );

        $this->assertNull(
            $snapshot->mature_trial_conversion()
        );
    }

    public function test_percentage_is_rounded_to_one_decimal(): void {
        $snapshot = new DashboardFunnelSnapshot(
            100,
            200,
            3,
            2,
            1,
            2,
            1,
            0,
            1,
            0
        );

        $this->assertSame(
            66.7,
            $snapshot->user_trial_ratio()
        );

        $this->assertSame(
            50.0,
            $snapshot->observed_trial_conversion()
        );
    }
}