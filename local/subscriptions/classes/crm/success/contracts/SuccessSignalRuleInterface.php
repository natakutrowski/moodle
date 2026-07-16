<?php

namespace local_subscriptions\crm\success\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Converts normalized metrics into explainable business signals.
 */
interface SuccessSignalRuleInterface {

    /**
     * Returns a stable technical rule key.
     */
    public function key(): string;

    /**
     * Returns whether the rule has enough relevant metrics to run.
     */
    public function supports(
        SuccessMetricCollection $metrics
    ): bool;

    /**
     * Evaluates the metrics and returns zero or more signals.
     *
     * The rule must not query Moodle or the database.
     */
    public function evaluate(
        SuccessMetricCollection $metrics,
        int $detectedat
    ): SuccessSignalCollection;
}