<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanCreationResult;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRepository;
use local_subscriptions\crm\success\plans\logging\CustomerSuccessPlanAdminEventLogger;

/**
 * Builds, deduplicates and persists Customer Success plan drafts.
 */
final class CustomerSuccessPlanCreationService {

    public function __construct(
        private readonly CustomerSuccessPlanBuilder $builder =
            new CustomerSuccessPlanBuilder(),

        private readonly CustomerSuccessPlanReadRepository $reader =
            new CustomerSuccessPlanReadRepository(),

        private readonly CustomerSuccessPlanRepository $writer =
            new CustomerSuccessPlanRepository(),

        private readonly CustomerSuccessPlanAdminEventLogger $events =
            new CustomerSuccessPlanAdminEventLogger()
    ) {
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     */
    public function create_from_recommendations(
        int $userid,
        array $recommendations,
        int $actorid,
        string $source =
            CustomerSuccessPlanSource::RECOMMENDATION_ENGINE
    ): CustomerSuccessPlanCreationResult {
        if ($actorid < 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan actor ID cannot be negative.'
            );
        }

        $draft = $this->builder->build(
            $userid,
            $recommendations,
            $source
        );

        $existing =
            $this->reader
                ->find_open_by_fingerprint(
                    $draft->fingerprint
                );

        if ($existing !== null) {
            return new CustomerSuccessPlanCreationResult(
                planid:
                    $existing->id,
                created: false,
                duplicate: true,
                stepcount:
                    $existing->step_count()
            );
        }

        $created =
            $this->writer
                ->create_from_draft(
                    $draft,
                    $actorid
                );

        $this->events->plan_created(
            (int)$created['planid'],
            $actorid
        );

        return new CustomerSuccessPlanCreationResult(
            planid:
                $created['planid'],
            created: true,
            duplicate: false,
            stepcount:
                count($created['stepids'])
        );
    }
}