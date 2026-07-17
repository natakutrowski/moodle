<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanDraft;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStepDraft;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;

/**
 * Builds a non-persistent plan from normalized recommendations.
 */
final class CustomerSuccessPlanBuilder {

    public function __construct(
        private readonly CustomerSuccessPrioritizer $prioritizer =
            new CustomerSuccessPrioritizer(),

        private readonly CustomerSuccessDependencyResolver $dependencies =
            new CustomerSuccessDependencyResolver(),

        private readonly CustomerSuccessPlanObjectivePolicy $objectives =
            new CustomerSuccessPlanObjectivePolicy(),

        private readonly CustomerSuccessPlanFingerprint $fingerprints =
            new CustomerSuccessPlanFingerprint()
    ) {
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     */
    public function build(
        int $userid,
        array $recommendations,
        string $source =
            CustomerSuccessPlanSource::RECOMMENDATION_ENGINE
    ): CustomerSuccessPlanDraft {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan user ID must be greater than zero.'
            );
        }

        if (
            !CustomerSuccessPlanSource::is_valid(
                $source
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan source.'
            );
        }

        $recommendations = array_values(
            array_filter(
                $recommendations,
                static fn(
                    CustomerSuccessRecommendationInput $recommendation
                ): bool =>
                    $recommendation->userid === $userid &&
                    !$recommendation->is_expired()
            )
        );

        if ($recommendations === []) {
            throw new \InvalidArgumentException(
                'No active recommendation is available for this Customer Success plan.'
            );
        }

        /*
         * Priority order is used as the stable initial order.
         * The dependency resolver may move prerequisite actions before it.
         */
        $recommendations =
            $this->prioritizer->sort(
                $recommendations
            );

        $dependencyresult =
            $this->dependencies->resolve(
                $recommendations
            );

        $scores =
            $this->prioritizer->score_all(
                $recommendations
            );

        $steps = [];
        $position = 1;

        foreach (
            $dependencyresult->orderedrecommendations
            as $recommendation
        ) {
            $dependency =
                $dependencyresult->dependency_for(
                    $recommendation
                        ->recommendationid
                );

            $iscyclic = in_array(
                $recommendation->recommendationid,
                $dependencyresult
                    ->cyclicrecommendationids,
                true
            );

            $priorityscore =
                $scores[
                    $recommendation->recommendationid
                ];

            $steps[] =
                new CustomerSuccessPlanStepDraft(
                    position: $position,
                    stepkey:
                        $this->step_key(
                            $recommendation
                        ),
                    title:
                        $recommendation->title,
                    description:
                        $recommendation->description,
                    priority:
                        $priorityscore->priority,
                    recommendationid:
                        $recommendation
                            ->recommendationid,
                    dependsonrecommendationid:
                        $dependency
                            ?->dependsonrecommendationid,
                    blockedreason:
                        $iscyclic
                            ? 'dependency_cycle'
                            : null,
                    priorityscore:
                        $priorityscore->score
                );

            $position++;
        }

        $objectivekey =
            $this->objectives->objective_key(
                $recommendations
            );

        $highestscore = max(
            array_map(
                static fn(
                    CustomerSuccessPlanStepDraft $step
                ): float => $step->priorityscore,
                $steps
            )
        );

        return new CustomerSuccessPlanDraft(
            userid: $userid,
            objectivekey:
                $objectivekey,
            title:
                $this->objectives->title(
                    $objectivekey
                ),
            description:
                $this->description(
                    $steps
                ),
            priority:
                $this->prioritizer
                    ->score_to_priority(
                        $highestscore
                    ),
            source: $source,
            fingerprint:
                $this->fingerprints->build(
                    $userid,
                    $objectivekey,
                    $recommendations
                ),
            steps: $steps
        );
    }

    private function step_key(
        CustomerSuccessRecommendationInput $recommendation
    ): string {
        $key = trim(
            $recommendation->actionkey !== ''
                ? $recommendation->actionkey
                : $recommendation
                    ->recommendationkey
        );

        $key =
            \core_text::strtolower(
                $key
            );

        $key = preg_replace(
            '/[^a-z0-9_.-]+/',
            '_',
            $key
        ) ?? '';

        $key = trim(
            $key,
            '_'
        );

        if ($key === '') {
            $key = 'recommendation';
        }

        return \core_text::substr(
            $key .
            '_' .
            $recommendation->recommendationid,
            0,
            100
        );
    }

    /**
     * @param CustomerSuccessPlanStepDraft[] $steps
     */
    private function description(
        array $steps
    ): string {
        return
            CustomerSuccessPlanPresentation::
                generated_description_value(
                    count($steps)
                );
    }
}