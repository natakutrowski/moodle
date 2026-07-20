<?php

namespace local_subscriptions\dashboard\funnel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Builds the Dashboard Funnel for the selected period.
 */
final class DashboardFunnelService {

    private const CONVERSION_WINDOW_DAYS = 30;

    public function __construct(
        private readonly DashboardFunnelRepository $repository =
            new DashboardFunnelRepository()
    ) {
    }

    public function load(
        string $period
    ): DashboardFunnelComparison {
        $period = DashboardPeriod::normalize($period);

        $currentrange =
            DashboardPeriod::range($period);

        $previousrange =
            DashboardPeriod::previous_range($period);

        return new DashboardFunnelComparison(
            $this->repository->snapshot(
                $currentrange['start'],
                $currentrange['end'],
                self::CONVERSION_WINDOW_DAYS
            ),
            $this->repository->snapshot(
                $previousrange['start'],
                $previousrange['end'],
                self::CONVERSION_WINDOW_DAYS
            ),
            self::CONVERSION_WINDOW_DAYS
        );
    }
}