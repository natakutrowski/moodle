<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

/**
 * Comparison of one CRM trend between two equivalent periods.
 */
final class DashboardTrendComparison {

    public function __construct(
        public readonly DashboardTrendMetric $current,
        public readonly DashboardTrendMetric $previous
    ) {
    }

    /**
     * Absolute difference in affected users.
     */
    public function difference(): int {
        return
            $this->current->value
            - $this->previous->value;
    }

    /**
     * Percentage variation.
     *
     * Null means that the previous period had no affected users,
     * therefore a percentage variation would be misleading.
     */
    public function variation(): ?float {
        if ($this->previous->value <= 0) {
            return null;
        }

        return round(
            (
                $this->difference()
                / $this->previous->value
            ) * 100,
            1
        );
    }

    /**
     * Whether the affected-user volume is stable.
     */
    public function is_stable(): bool {
        return $this->difference() === 0;
    }

    /**
     * Whether the business situation improved.
     *
     * For a positive metric, an increase is favourable.
     * For a negative metric, a decrease is favourable.
     */
    public function business_is_improving(): bool {
        $difference = $this->difference();

        if ($difference === 0) {
            return false;
        }

        if ($this->current->is_improving()) {
            return $difference > 0;
        }

        if ($this->current->is_degrading()) {
            return $difference < 0;
        }

        return false;
    }

    /**
     * Whether the business situation degraded.
     */
    public function business_is_degrading(): bool {
        $difference = $this->difference();

        if ($difference === 0) {
            return false;
        }

        if ($this->current->is_improving()) {
            return $difference < 0;
        }

        if ($this->current->is_degrading()) {
            return $difference > 0;
        }

        return false;
    }
}