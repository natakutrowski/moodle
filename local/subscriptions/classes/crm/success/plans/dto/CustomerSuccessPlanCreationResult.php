<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of creating or deduplicating a Customer Success plan.
 */
final class CustomerSuccessPlanCreationResult {

    public function __construct(
        public readonly int $planid,
        public readonly bool $created,
        public readonly bool $duplicate,
        public readonly int $stepcount
    ) {
        if ($this->planid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan creation result ID must be greater than zero.'
            );
        }
    }
}