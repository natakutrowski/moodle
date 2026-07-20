<?php

namespace local_subscriptions\dashboard\funnel;

defined('MOODLE_INTERNAL') || die();

/**
 * Current and previous Funnel snapshots.
 */
final class DashboardFunnelComparison {

    public function __construct(
        public readonly DashboardFunnelSnapshot $current,
        public readonly DashboardFunnelSnapshot $previous,
        public readonly int $conversionwindowdays
    ) {
    }

    /**
     * Calculate an absolute difference.
     *
     * @param int $current
     * @param int $previous
     * @return int
     */
    public function difference(
        int $current,
        int $previous
    ): int {
        return $current - $previous;
    }

    /**
     * Calculate a percentage variation.
     *
     * Null means that the previous value was zero and no meaningful
     * percentage variation can be calculated.
     *
     * @param int $current
     * @param int $previous
     * @return float|null
     */
    public function variation(
        int $current,
        int $previous
    ): ?float {
        if ($previous <= 0) {
            return null;
        }

        return round(
            (($current - $previous) / $previous) * 100,
            1
        );
    }

    /**
     * Calculate the difference between two nullable rates.
     *
     * The result is expressed in percentage points.
     *
     * @param float|null $current
     * @param float|null $previous
     * @return float|null
     */
    public function rate_difference(
        ?float $current,
        ?float $previous
    ): ?float {
        if ($current === null || $previous === null) {
            return null;
        }

        return round(
            $current - $previous,
            1
        );
    }

    public function new_users_difference(): int {
        return $this->difference(
            $this->current->newusers,
            $this->previous->newusers
        );
    }

    public function new_users_variation(): ?float {
        return $this->variation(
            $this->current->newusers,
            $this->previous->newusers
        );
    }

    public function trial_users_difference(): int {
        return $this->difference(
            $this->current->trialusers,
            $this->previous->trialusers
        );
    }

    public function trial_users_variation(): ?float {
        return $this->variation(
            $this->current->trialusers,
            $this->previous->trialusers
        );
    }

    public function new_customers_difference(): int {
        return $this->difference(
            $this->current->newcustomers,
            $this->previous->newcustomers
        );
    }

    public function new_customers_variation(): ?float {
        return $this->variation(
            $this->current->newcustomers,
            $this->previous->newcustomers
        );
    }

    public function digital_buyers_difference(): int {
        return $this->difference(
            $this->current->digitalbuyers,
            $this->previous->digitalbuyers
        );
    }

    public function digital_buyers_variation(): ?float {
        return $this->variation(
            $this->current->digitalbuyers,
            $this->previous->digitalbuyers
        );
    }

    /**
     * Difference between observed cohort conversion rates.
     *
     * @return float|null Percentage points.
     */
    public function observed_conversion_difference(): ?float {
        return $this->rate_difference(
            $this->current->observed_trial_conversion(),
            $this->previous->observed_trial_conversion()
        );
    }

    /**
     * Difference between mature cohort conversion rates.
     *
     * @return float|null Percentage points.
     */
    public function mature_conversion_difference(): ?float {
        return $this->rate_difference(
            $this->current->mature_trial_conversion(),
            $this->previous->mature_trial_conversion()
        );
    }
}