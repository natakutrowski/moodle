<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Non-persistent Customer Success plan step.
 */
final class CustomerSuccessPlanStepDraft {

    public function __construct(
        public readonly int $position,
        public readonly string $stepkey,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $priority,
        public readonly int $recommendationid,
        public readonly ?int $dependsonrecommendationid,
        public readonly ?string $blockedreason,
        public readonly float $priorityscore
    ) {
        if ($this->position <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan draft step position must be greater than zero.'
            );
        }

        if ($this->recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan draft recommendation ID must be greater than zero.'
            );
        }

        if (trim($this->stepkey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan draft step key is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan draft step title is required.'
            );
        }
    }
}