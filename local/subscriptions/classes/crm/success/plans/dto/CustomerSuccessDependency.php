<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Directed dependency between two recommendation-backed actions.
 */
final class CustomerSuccessDependency {

    public function __construct(
        public readonly int $recommendationid,
        public readonly int $dependsonrecommendationid,
        public readonly string $reasonkey
    ) {
        if (
            $this->recommendationid <= 0 ||
            $this->dependsonrecommendationid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Customer Success dependency IDs must be greater than zero.'
            );
        }

        if (
            $this->recommendationid ===
            $this->dependsonrecommendationid
        ) {
            throw new \InvalidArgumentException(
                'A Customer Success action cannot depend on itself.'
            );
        }

        if (trim($this->reasonkey) === '') {
            throw new \InvalidArgumentException(
                'Customer Success dependency reason is required.'
            );
        }
    }
}