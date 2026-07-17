<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;

/**
 * Recommendation data normalized for the Customer Success planner.
 *
 * The planner deliberately does not depend on the persistence DTO used by
 * the Recommendation Engine.
 */
final class CustomerSuccessRecommendationInput {

    public function __construct(
        public readonly int $recommendationid,
        public readonly int $userid,
        public readonly string $recommendationkey,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $category,
        public readonly string $priority,
        public readonly string $actionkey,
        public readonly int $impactscore,
        public readonly int $urgencyscore,
        public readonly int $valuescore,
        public readonly int $effortscore,
        public readonly ?int $validuntil = null,
        public readonly array $metadata = []
    ) {
        if ($this->recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success recommendation ID must be greater than zero.'
            );
        }

        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success recommendation user ID must be greater than zero.'
            );
        }

        if (trim($this->recommendationkey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success recommendation key is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Customer Success recommendation title is required.'
            );
        }

        if (
            !CustomerSuccessActionCategory::is_valid(
                $this->category
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success recommendation category.'
            );
        }

        foreach (
            [
                $this->impactscore,
                $this->urgencyscore,
                $this->valuescore,
                $this->effortscore,
            ]
            as $score
        ) {
            if ($score < 0 || $score > 100) {
                throw new \InvalidArgumentException(
                    'Customer Success recommendation scores must be between 0 and 100.'
                );
            }
        }
    }

    public function is_expired(
        ?int $now = null
    ): bool {
        if ($this->validuntil === null) {
            return false;
        }

        return $this->validuntil <=
            ($now ?? time());
    }
}