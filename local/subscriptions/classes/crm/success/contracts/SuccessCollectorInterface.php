<?php

namespace local_subscriptions\crm\success\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetricCollection;

/**
 * Collects normalized factual metrics from one data source.
 *
 * A collector must not calculate scores or create business signals.
 */
interface SuccessCollectorInterface {

    /**
     * Returns a stable technical collector key.
     */
    public function key(): string;

    /**
     * Returns whether the underlying source is currently available.
     *
     * Optional third-party plugins must return false rather than fail when
     * they are not installed or are unavailable.
     */
    public function is_available(): bool;

    /**
     * Collects normalized metrics for one Moodle user.
     *
     * @param int $userid Moodle user ID.
     * @param int $measuredat Reference timestamp shared by the runtime.
     */
    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection;
}