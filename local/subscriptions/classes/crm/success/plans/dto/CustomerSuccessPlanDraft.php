<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Non-persistent Customer Success plan.
 */
final class CustomerSuccessPlanDraft {

    /**
     * @param CustomerSuccessPlanStepDraft[] $steps
     */
    public function __construct(
        public readonly int $userid,
        public readonly string $objectivekey,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $priority,
        public readonly string $source,
        public readonly string $fingerprint,
        public readonly array $steps
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan draft user ID must be greater than zero.'
            );
        }

        if (trim($this->objectivekey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan draft objective key is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan draft title is required.'
            );
        }

        if ($this->steps === []) {
            throw new \InvalidArgumentException(
                'Customer Success plan draft requires at least one step.'
            );
        }

        foreach ($this->steps as $step) {
            if (
                !$step instanceof
                CustomerSuccessPlanStepDraft
            ) {
                throw new \InvalidArgumentException(
                    'Customer Success plan draft contains an invalid step.'
                );
            }
        }
    }
}