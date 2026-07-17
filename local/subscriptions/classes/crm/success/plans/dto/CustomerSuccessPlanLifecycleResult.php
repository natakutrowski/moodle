<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a Customer Success plan lifecycle action.
 */
final class CustomerSuccessPlanLifecycleResult {

    public function __construct(
        public readonly int $planid,
        public readonly ?int $stepid,
        public readonly string $action,
        public readonly string $previousstatus,
        public readonly string $newstatus,
        public readonly bool $planautocompleted = false
    ) {
        if ($this->planid <= 0) {
            throw new \InvalidArgumentException(
                'Lifecycle result plan ID must be greater than zero.'
            );
        }

        if ($this->stepid !== null && $this->stepid <= 0) {
            throw new \InvalidArgumentException(
                'Lifecycle result step ID must be greater than zero.'
            );
        }
    }
}