<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\trends\DashboardTrendsRepository;

/**
 * Tests for persisted CRM score trends.
 *
 * @covers \local_subscriptions\dashboard\trends\DashboardTrendsRepository
 */
final class DashboardTrendsRepositoryTest extends advanced_testcase {

    /**
     * Insert one persisted score snapshot.
     */
    private function create_score(
        int $userid,
        int $engagement,
        int $risk,
        int $global,
        int $timecreated
    ): int {
        global $DB;

        return (int)$DB->insert_record(
            'local_subscriptions_crm_score',
            (object)[
                'userid' => $userid,
                'commercialscore' => 0,
                'engagementscore' => $engagement,
                'riskscore' => $risk,
                'globalscore' => $global,
                'level' => 'test',
                'segmentsjson' => null,
                'opportunitiesjson' => null,
                'recommendationsjson' => null,
                'timecreated' => $timecreated,
            ]
        );
    }

    public function test_repository_detects_significant_movements(): void {
        $this->resetAfterTest(true);

        $start = time() - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $improving =
            $this->getDataGenerator()
                ->create_user();

        $degrading =
            $this->getDataGenerator()
                ->create_user();

        $this->create_score(
            (int)$improving->id,
            20,
            60,
            30,
            $start - DAYSECS
        );

        $this->create_score(
            (int)$improving->id,
            30,
            45,
            40,
            $start + 100
        );

        $this->create_score(
            (int)$degrading->id,
            60,
            20,
            70,
            $start - DAYSECS
        );

        $this->create_score(
            (int)$degrading->id,
            50,
            30,
            60,
            $start + 200
        );

        $snapshot = (
            new DashboardTrendsRepository()
        )->snapshot(
            $start,
            $end,
            5
        );

        $this->assertSame(
            2,
            $snapshot->currentusers
        );

        $this->assertSame(
            2,
            $snapshot->analysedusers
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_ENGAGEMENT_UP
            )->value
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_RISK_DOWN
            )->value
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_GLOBAL_UP
            )->value
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_ENGAGEMENT_DOWN
            )->value
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_RISK_UP
            )->value
        );

        $this->assertSame(
            1,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_GLOBAL_DOWN
            )->value
        );
    }

    public function test_latest_snapshot_inside_period_is_used(): void {
        $this->resetAfterTest(true);

        $start = time() - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user =
            $this->getDataGenerator()
                ->create_user();

        $this->create_score(
            (int)$user->id,
            20,
            50,
            30,
            $start - DAYSECS
        );

        /*
         * First snapshot is improving.
         */
        $this->create_score(
            (int)$user->id,
            40,
            30,
            50,
            $start + 100
        );

        /*
         * Last snapshot returns to baseline.
         * This is the one that must be used.
         */
        $this->create_score(
            (int)$user->id,
            21,
            49,
            31,
            $start + 200
        );

        $snapshot = (
            new DashboardTrendsRepository()
        )->snapshot(
            $start,
            $end,
            5
        );

        $this->assertSame(
            0,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_ENGAGEMENT_UP
            )->value
        );

        $this->assertSame(
            0,
            $snapshot->metric(
                DashboardTrendsRepository::
                    METRIC_GLOBAL_UP
            )->value
        );
    }

    public function test_user_without_baseline_is_not_compared(): void {
        $this->resetAfterTest(true);

        $start = time() - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user =
            $this->getDataGenerator()
                ->create_user();

        $this->create_score(
            (int)$user->id,
            80,
            10,
            90,
            $start + 100
        );

        $snapshot = (
            new DashboardTrendsRepository()
        )->snapshot(
            $start,
            $end,
            5
        );

        $this->assertSame(
            1,
            $snapshot->currentusers
        );

        $this->assertSame(
            0,
            $snapshot->analysedusers
        );

        $this->assertFalse(
            $snapshot->has_comparable_data()
        );
    }

    public function test_small_movements_are_ignored(): void {
        $this->resetAfterTest(true);

        $start = time() - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user =
            $this->getDataGenerator()
                ->create_user();

        $this->create_score(
            (int)$user->id,
            50,
            50,
            50,
            $start - DAYSECS
        );

        $this->create_score(
            (int)$user->id,
            53,
            47,
            52,
            $start + 100
        );

        $snapshot = (
            new DashboardTrendsRepository()
        )->snapshot(
            $start,
            $end,
            5
        );

        $this->assertFalse(
            $snapshot->has_movements()
        );
    }
}