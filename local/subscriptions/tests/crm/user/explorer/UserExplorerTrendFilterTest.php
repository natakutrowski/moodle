<?php

namespace local_subscriptions\tests\crm\user\explorer;

use advanced_testcase;
use local_subscriptions\crm\user\explorer\UserExplorerTrendFilter;
use local_subscriptions\dashboard\trends\DashboardTrendsRepository;

/**
 * Tests for User Explorer trend filter normalization.
 *
 * @covers \local_subscriptions\crm\user\explorer\UserExplorerTrendFilter
 */
final class UserExplorerTrendFilterTest
        extends advanced_testcase {

    public function test_valid_filter_is_active(): void {
        $filter =
            UserExplorerTrendFilter::create(
                DashboardTrendsRepository::
                    METRIC_ENGAGEMENT_UP,
                1000,
                2000,
                5
            );

        $this->assertTrue(
            $filter->is_active()
        );

        $this->assertSame(
            'engagementscore',
            $filter->score_field()
        );

        $this->assertTrue(
            $filter->expects_increase()
        );
    }

    public function test_decreasing_filter_uses_negative_direction():
            void {
        $filter =
            UserExplorerTrendFilter::create(
                DashboardTrendsRepository::
                    METRIC_GLOBAL_DOWN,
                1000,
                2000,
                5
            );

        $this->assertTrue(
            $filter->is_active()
        );

        $this->assertSame(
            'globalscore',
            $filter->score_field()
        );

        $this->assertFalse(
            $filter->expects_increase()
        );
    }

    public function test_unknown_filter_is_rejected(): void {
        $filter =
            UserExplorerTrendFilter::create(
                'malicious_column',
                1000,
                2000,
                5
            );

        $this->assertFalse(
            $filter->is_active()
        );

        $this->assertSame(
            '',
            $filter->trend
        );
    }

    public function test_invalid_range_is_rejected(): void {
        $filter =
            UserExplorerTrendFilter::create(
                DashboardTrendsRepository::
                    METRIC_RISK_UP,
                2000,
                1000,
                5
            );

        $this->assertFalse(
            $filter->is_active()
        );
    }

    public function test_delta_is_normalized(): void {
        $filter =
            UserExplorerTrendFilter::create(
                DashboardTrendsRepository::
                    METRIC_RISK_DOWN,
                1000,
                2000,
                500
            );

        $this->assertSame(
            100,
            $filter->delta
        );
    }

    public function test_params_are_empty_when_inactive(): void {
        $this->assertSame(
            [],
            UserExplorerTrendFilter::none()
                ->params()
        );
    }
}