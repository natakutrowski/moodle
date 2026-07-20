<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\funnel\DashboardFunnelComparison;
use local_subscriptions\dashboard\funnel\DashboardFunnelSnapshot;

/**
 * Tests for Dashboard Funnel period comparison.
 *
 * @covers \local_subscriptions\dashboard\funnel\DashboardFunnelComparison
 */
final class DashboardFunnelComparisonTest extends advanced_testcase {

    private function snapshot(
        int $newusers,
        int $trialusers,
        int $convertedtrialusers,
        int $maturetrialusers,
        int $convertedmaturetrialusers,
        int $newcustomers,
        int $digitalbuyers
    ): DashboardFunnelSnapshot {
        return new DashboardFunnelSnapshot(
            100,
            200,
            $newusers,
            $trialusers,
            $convertedtrialusers,
            $maturetrialusers,
            $convertedmaturetrialusers,
            max(
                0,
                $trialusers - $maturetrialusers
            ),
            $newcustomers,
            $digitalbuyers
        );
    }

    public function test_volume_variations_are_calculated(): void {
        $comparison = new DashboardFunnelComparison(
            $this->snapshot(
                12,
                8,
                4,
                8,
                4,
                6,
                3
            ),
            $this->snapshot(
                10,
                4,
                1,
                4,
                1,
                4,
                2
            ),
            30
        );

        $this->assertSame(
            2,
            $comparison->new_users_difference()
        );

        $this->assertSame(
            20.0,
            $comparison->new_users_variation()
        );

        $this->assertSame(
            100.0,
            $comparison->trial_users_variation()
        );

        $this->assertSame(
            50.0,
            $comparison->new_customers_variation()
        );

        $this->assertSame(
            50.0,
            $comparison->digital_buyers_variation()
        );
    }

    public function test_zero_previous_volume_returns_null_variation(): void {
        $comparison = new DashboardFunnelComparison(
            $this->snapshot(
                5,
                2,
                1,
                2,
                1,
                1,
                0
            ),
            $this->snapshot(
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            30
        );

        $this->assertSame(
            5,
            $comparison->new_users_difference()
        );

        $this->assertNull(
            $comparison->new_users_variation()
        );
    }

    public function test_conversion_difference_uses_points(): void {
        $comparison = new DashboardFunnelComparison(
            $this->snapshot(
                20,
                10,
                5,
                8,
                4,
                5,
                2
            ),
            $this->snapshot(
                20,
                10,
                3,
                8,
                2,
                3,
                1
            ),
            30
        );

        /*
         * Observed conversion:
         * current 5 / 10 = 50%
         * previous 3 / 10 = 30%
         */
        $this->assertSame(
            20.0,
            $comparison->observed_conversion_difference()
        );

        /*
         * Mature conversion:
         * current 4 / 8 = 50%
         * previous 2 / 8 = 25%
         */
        $this->assertSame(
            25.0,
            $comparison->mature_conversion_difference()
        );
    }

    public function test_unavailable_rate_returns_null_difference(): void {
        $comparison = new DashboardFunnelComparison(
            $this->snapshot(
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ),
            $this->snapshot(
                10,
                5,
                2,
                4,
                2,
                2,
                1
            ),
            30
        );

        $this->assertNull(
            $comparison->observed_conversion_difference()
        );

        $this->assertNull(
            $comparison->mature_conversion_difference()
        );
    }
}