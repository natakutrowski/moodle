<?php

namespace local_subscriptions\dashboard\funnel;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable Funnel values for one period.
 */
final class DashboardFunnelSnapshot {

    public function __construct(
        public readonly int $start,
        public readonly int $end,
        public readonly int $newusers,
        public readonly int $trialusers,
        public readonly int $convertedtrialusers,
        public readonly int $maturetrialusers,
        public readonly int $convertedmaturetrialusers,
        public readonly int $pendingtrialusers,
        public readonly int $newcustomers,
        public readonly int $digitalbuyers
    ) {
    }

    /**
     * Account creation to trial-start ratio.
     *
     * This ratio is contextual rather than strictly cohort-based because
     * an existing Moodle user may start a trial during the period.
     */
    public function user_trial_ratio(): ?float {
        return self::percentage(
            $this->trialusers,
            $this->newusers
        );
    }

    /**
     * Observed conversion of the selected trial cohort.
     *
     * Every trial user belongs to the cohort. Recent users may still be
     * inside the observation window, so this rate can evolve.
     */
    public function observed_trial_conversion(): ?float {
        return self::percentage(
            $this->convertedtrialusers,
            $this->trialusers
        );
    }

    /**
     * Final conversion among users whose full observation window elapsed.
     */
    public function mature_trial_conversion(): ?float {
        return self::percentage(
            $this->convertedmaturetrialusers,
            $this->maturetrialusers
        );
    }

    /**
     * Return a percentage or null when the denominator is empty.
     */
    private static function percentage(
        int $numerator,
        int $denominator
    ): ?float {
        if ($denominator <= 0) {
            return null;
        }

        return round(
            ($numerator / $denominator) * 100,
            1
        );
    }
}