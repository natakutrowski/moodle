<?php

namespace local_subscriptions\crm\assistant\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria;
use local_subscriptions\crm\assistant\dto\AssistantWorkspace;
use local_subscriptions\crm\assistant\repositories\AssistantRecommendationRepository;

/**
 * Application service for the CRM Assistant workspace.
 */
final class CrmAssistantService {

    public function __construct(
        private readonly AssistantRecommendationRepository $repository =
            new AssistantRecommendationRepository()
    ) {
    }

    public function workspace(
        AssistantRecommendationCriteria $criteria
    ): AssistantWorkspace {
        return new AssistantWorkspace(
            overview:
                $this->repository->get_overview(),
            recommendations:
                $this->repository->search($criteria),
            criteria: $criteria,
            total:
                $this->repository->count($criteria)
        );
    }

    public function user_recommendations(
        int $userid,
        int $limit = 10
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'CRM Assistant user ID must be greater than zero.'
            );
        }

        return $this->repository->get_for_user(
            $userid,
            $limit
        );
    }
}