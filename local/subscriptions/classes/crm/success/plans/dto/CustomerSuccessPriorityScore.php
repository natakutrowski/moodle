<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Deterministic priority score for one plan action.
 */
final class CustomerSuccessPriorityScore {

    public function __construct(
        public readonly int $recommendationid,
        public readonly float $score,
        public readonly int $impactscore,
        public readonly int $urgencyscore,
        public readonly int $valuescore,
        public readonly int $effortscore,
        public readonly string $priority
    ) {
        if ($this->recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Priority score recommendation ID must be greater than zero.'
            );
        }

        if ($this->score < 0 || $this->score > 100) {
            throw new \InvalidArgumentException(
                'Priority score must be between 0 and 100.'
            );
        }
    }
}