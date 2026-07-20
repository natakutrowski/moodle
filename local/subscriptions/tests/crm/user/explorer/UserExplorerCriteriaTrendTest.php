<?php

namespace local_subscriptions\tests\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerTrendFilter;
use local_subscriptions\dashboard\trends\DashboardTrendsRepository;

/**
 * Tests trend-filter preservation in UserExplorerCriteria.
 *
 * @covers \local_subscriptions\crm\user\explorer\UserExplorerCriteria
 */
final class UserExplorerCriteriaTrendTest
        extends advanced_testcase {

    /**
     * Build criteria with a valid trend filter.
     */
    private function criteria(): UserExplorerCriteria {
        return new UserExplorerCriteria(
            query: 'campus',
            hasinbox:
                UserExplorerCriteria::PRESENCE_YES,
            hasinboxunread:
                UserExplorerCriteria::PRESENCE_YES,
            trendfilter:
                UserExplorerTrendFilter::create(
                    DashboardTrendsRepository::
                        METRIC_GLOBAL_DOWN,
                    1000,
                    2000,
                    5
                )
        );
    }

    public function test_saved_params_include_trend():
            void {
        $params =
            $this->criteria()->saved_params();

        $this->assertSame(
            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN,
            $params['trend']
        );

        $this->assertSame(
            1000,
            $params['trendstart']
        );

        $this->assertSame(
            2000,
            $params['trendend']
        );

        $this->assertSame(
            5,
            $params['trenddelta']
        );
    }

    public function test_url_params_include_trend():
            void {
        $params =
            $this->criteria()->url_params();

        $this->assertArrayHasKey(
            'trend',
            $params
        );

        $this->assertArrayHasKey(
            'trendstart',
            $params
        );

        $this->assertArrayHasKey(
            'trendend',
            $params
        );

        $this->assertArrayHasKey(
            'trenddelta',
            $params
        );
    }

    public function test_with_page_preserves_trend():
            void {
        $criteria =
            $this->criteria()->with_page(3);

        $this->assertSame(
            3,
            $criteria->page
        );

        $this->assertTrue(
            $criteria->trendfilter
                ->is_active()
        );

        $this->assertSame(
            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN,
            $criteria->trendfilter->trend
        );

        $this->assertSame(
            1000,
            $criteria->trendfilter->start
        );

        $this->assertSame(
            2000,
            $criteria->trendfilter->end
        );
    }

    public function test_without_inbox_preserves_trend():
            void {
        $criteria =
            $this->criteria()
                ->without_inbox();

        $this->assertSame(
            UserExplorerCriteria::PRESENCE_ALL,
            $criteria->hasinbox
        );

        $this->assertSame(
            UserExplorerCriteria::PRESENCE_ALL,
            $criteria->hasinboxunread
        );

        $this->assertTrue(
            $criteria->trendfilter
                ->is_active()
        );

        $this->assertSame(
            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN,
            $criteria->trendfilter->trend
        );
    }

    public function test_active_trend_counts_as_one_filter():
            void {
        $criteria = new UserExplorerCriteria(
            trendfilter:
                UserExplorerTrendFilter::create(
                    DashboardTrendsRepository::
                        METRIC_RISK_UP,
                    1000,
                    2000,
                    5
                )
        );

        $this->assertSame(
            1,
            $criteria->active_filter_count()
        );
    }

    public function test_trend_is_added_to_other_filters():
            void {
        $criteria = new UserExplorerCriteria(
            query: 'nata',
            country: 'FR',
            trendfilter:
                UserExplorerTrendFilter::create(
                    DashboardTrendsRepository::
                        METRIC_RISK_UP,
                    1000,
                    2000,
                    5
                )
        );

        $this->assertSame(
            3,
            $criteria->active_filter_count()
        );
    }

    public function test_empty_trend_is_not_counted():
            void {
        $criteria =
            new UserExplorerCriteria();

        $this->assertSame(
            0,
            $criteria->active_filter_count()
        );
    }

    public function test_saved_params_restore_trend():
            void {
        $original =
            $this->criteria();

        $restored =
            UserExplorerCriteria::
                from_saved_params(
                    $original->saved_params()
                );

        $this->assertTrue(
            $restored->trendfilter
                ->is_active()
        );

        $this->assertSame(
            $original->trendfilter->trend,
            $restored->trendfilter->trend
        );

        $this->assertSame(
            $original->trendfilter->start,
            $restored->trendfilter->start
        );

        $this->assertSame(
            $original->trendfilter->end,
            $restored->trendfilter->end
        );

        $this->assertSame(
            $original->trendfilter->delta,
            $restored->trendfilter->delta
        );
    }
}