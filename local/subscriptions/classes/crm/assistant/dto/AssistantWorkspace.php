<?php

namespace local_subscriptions\crm\assistant\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Complete data required by the Assistant workspace renderer.
 */
final class AssistantWorkspace {

    /**
     * @param AssistantRecommendation[] $recommendations
     */
    public function __construct(
        public readonly AssistantOverview $overview,
        public readonly array $recommendations,
        public readonly AssistantRecommendationCriteria $criteria,
        public readonly int $total
    ) {
        foreach ($this->recommendations as $recommendation) {
            if (
                !$recommendation instanceof
                AssistantRecommendation
            ) {
                throw new \InvalidArgumentException(
                    'Assistant workspace requires AssistantRecommendation objects.'
                );
            }
        }
    }
}