<?php

namespace local_subscriptions\crm\success\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collectors\SuccessCollectorRegistry;
use local_subscriptions\crm\success\dto\CustomerSuccessResult;
use local_subscriptions\crm\success\scoring\SuccessScoreEngine;
use local_subscriptions\crm\success\signals\SuccessSignalEngine;

/**
 * Runs the complete Customer Success pipeline for one user.
 */
final class CustomerSuccessRuntime {

    public function __construct(
        private readonly SuccessCollectorRegistry $collectors,
        private readonly SuccessSignalEngine $signalengine,
        private readonly SuccessScoreEngine $scoreengine
    ) {
    }

    public function evaluate(
        int $userid,
        ?int $evaluatedat = null
    ): CustomerSuccessResult {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success runtime userid must be greater than zero.'
            );
        }

        $evaluatedat = $evaluatedat ?? time();

        if ($evaluatedat <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success runtime timestamp must be greater than zero.'
            );
        }

        $collection = $this->collectors->collect(
            $userid,
            $evaluatedat
        );

        $signalerrors = [];

        $signals = $this->signalengine->evaluate(
            $collection->metrics,
            $evaluatedat,
            $signalerrors
        );

        $score = $this->scoreengine->calculate(
            $userid,
            $signals,
            $evaluatedat
        );

        return new CustomerSuccessResult(
            $userid,
            $collection,
            $signals,
            $score,
            $signalerrors
        );
    }
}