<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Dependency resolution result.
 */
final class CustomerSuccessDependencyResult {

    /**
     * @param CustomerSuccessRecommendationInput[] $orderedrecommendations
     * @param CustomerSuccessDependency[] $dependencies
     * @param int[] $cyclicrecommendationids
     */
    public function __construct(
        public readonly array $orderedrecommendations,
        public readonly array $dependencies,
        public readonly array $cyclicrecommendationids = []
    ) {
        foreach (
            $this->orderedrecommendations
            as $recommendation
        ) {
            if (
                !$recommendation instanceof
                CustomerSuccessRecommendationInput
            ) {
                throw new \InvalidArgumentException(
                    'Dependency result contains an invalid recommendation.'
                );
            }
        }

        foreach ($this->dependencies as $dependency) {
            if (
                !$dependency instanceof
                CustomerSuccessDependency
            ) {
                throw new \InvalidArgumentException(
                    'Dependency result contains an invalid dependency.'
                );
            }
        }
    }

    public function has_cycles(): bool {
        return $this->cyclicrecommendationids !== [];
    }

    public function dependency_for(
        int $recommendationid
    ): ?CustomerSuccessDependency {
        foreach ($this->dependencies as $dependency) {
            if (
                $dependency->recommendationid ===
                $recommendationid
            ) {
                return $dependency;
            }
        }

        return null;
    }
}