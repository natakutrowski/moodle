<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregated CRM trends for one Dashboard period.
 */
final class DashboardTrendsSnapshot {

    /**
     * @param int $start Inclusive period start.
     * @param int $end Exclusive period end.
     * @param DashboardTrendMetric[] $metrics
     * @param int $analysedusers Users having both a baseline and current snapshot.
     * @param int $currentusers Users having a snapshot during the period.
     * @param int $freshness Latest score snapshot timestamp found.
     */
    public function __construct(
        public readonly int $start,
        public readonly int $end,
        private readonly array $metrics,
        public readonly int $analysedusers,
        public readonly int $currentusers,
        public readonly int $freshness
    ) {
    }

    /**
     * Return all metrics indexed by their stable key.
     *
     * @return DashboardTrendMetric[]
     */
    public function metrics(): array {
        return $this->metrics;
    }

    /**
     * Return one metric.
     */
    public function metric(
        string $key
    ): ?DashboardTrendMetric {
        return $this->metrics[$key] ?? null;
    }

    /**
     * Whether at least one user could be compared.
     */
    public function has_comparable_data(): bool {
        return $this->analysedusers > 0;
    }

    /**
     * Whether at least one score snapshot exists in the period.
     */
    public function has_current_data(): bool {
        return $this->currentusers > 0;
    }

    /**
     * Whether one or more actual movements were detected.
     */
    public function has_movements(): bool {
        foreach ($this->metrics as $metric) {
            if ($metric->has_value()) {
                return true;
            }
        }

        return false;
    }
}