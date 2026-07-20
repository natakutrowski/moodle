<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Loads and compares CRM trends for the Dashboard.
 */
final class DashboardTrendsService {

    public function __construct(
        private readonly DashboardTrendsRepository $repository =
            new DashboardTrendsRepository()
    ) {
    }

    /**
     * Load current and previous period trends.
     */
    public function load(
        string $period,
        int $significantdelta =
            DashboardTrendsRepository::
                DEFAULT_SIGNIFICANT_DELTA
    ): DashboardTrendsComparison {
        $period =
            DashboardPeriod::normalize($period);

        $currentrange =
            DashboardPeriod::range($period);

        $previousrange =
            DashboardPeriod::previous_range(
                $period
            );

        $current =
            $this->repository->snapshot(
                (int)$currentrange['start'],
                (int)$currentrange['end'],
                $significantdelta
            );

        $previous =
            $this->repository->snapshot(
                (int)$previousrange['start'],
                (int)$previousrange['end'],
                $significantdelta
            );

        $comparisons = [];

        $keys = array_unique(
            array_merge(
                array_keys($current->metrics()),
                array_keys($previous->metrics())
            )
        );

        foreach ($keys as $key) {
            $currentmetric =
                $current->metric($key);

            $previousmetric =
                $previous->metric($key);

            if (
                $currentmetric === null
                && $previousmetric === null
            ) {
                continue;
            }

            if ($currentmetric === null) {
                $currentmetric =
                    $this->empty_metric_from(
                        $previousmetric
                    );
            }

            if ($previousmetric === null) {
                $previousmetric =
                    $this->empty_metric_from(
                        $currentmetric
                    );
            }

            $comparisons[$key] =
                new DashboardTrendComparison(
                    $currentmetric,
                    $previousmetric
                );
        }

        return new DashboardTrendsComparison(
            $current,
            $previous,
            $comparisons
        );
    }

    /**
     * Create an empty equivalent metric.
     */
    private function empty_metric_from(
        DashboardTrendMetric $metric
    ): DashboardTrendMetric {
        return new DashboardTrendMetric(
            $metric->key,
            0,
            $metric->direction,
            $metric->severity,
            []
        );
    }
}