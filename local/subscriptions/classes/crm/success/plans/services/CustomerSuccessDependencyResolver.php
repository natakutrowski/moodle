<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependency;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessDependencyResult;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;
use local_subscriptions\crm\success\plans\services\dependencies\AccessBeforeLearningRule;
use local_subscriptions\crm\success\plans\services\dependencies\CustomerSuccessDependencyRuleInterface;
use local_subscriptions\crm\success\plans\services\dependencies\PaymentBeforeAccessRule;
use local_subscriptions\crm\success\plans\services\dependencies\SupportBeforeFollowUpRule;

/**
 * Resolves action dependencies and returns a stable topological order.
 */
final class CustomerSuccessDependencyResolver {

    /**
     * @var CustomerSuccessDependencyRuleInterface[]
     */
    private array $rules;

    /**
     * @param CustomerSuccessDependencyRuleInterface[]|null $rules
     */
    public function __construct(
        ?array $rules = null
    ) {
        $this->rules = $rules ?? [
            new PaymentBeforeAccessRule(),
            new SupportBeforeFollowUpRule(),
            new AccessBeforeLearningRule(),
        ];

        foreach ($this->rules as $rule) {
            if (
                !$rule instanceof
                CustomerSuccessDependencyRuleInterface
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success dependency rule.'
                );
            }
        }
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     */
    public function resolve(
        array $recommendations
    ): CustomerSuccessDependencyResult {
        $byid = [];

        foreach ($recommendations as $recommendation) {
            if (
                !$recommendation instanceof
                CustomerSuccessRecommendationInput
            ) {
                throw new \InvalidArgumentException(
                    'Dependency resolver requires CustomerSuccessRecommendationInput objects.'
                );
            }

            $byid[$recommendation->recommendationid] =
                $recommendation;
        }

        $dependencies = $this->detect_dependencies(
            array_values($byid)
        );

        [$orderedids, $cyclicids] =
            $this->topological_sort(
                array_keys($byid),
                $dependencies
            );

        /*
         * Cyclic actions are appended in their original stable order.
         * They will later be marked as blocked rather than silently lost.
         */
        foreach (array_keys($byid) as $recommendationid) {
            if (
                in_array(
                    $recommendationid,
                    $cyclicids,
                    true
                )
            ) {
                $orderedids[] =
                    $recommendationid;
            }
        }

        $ordered = array_map(
            static fn(int $recommendationid):
                CustomerSuccessRecommendationInput =>
                    $byid[$recommendationid],
            $orderedids
        );

        return new CustomerSuccessDependencyResult(
            orderedrecommendations:
                $ordered,
            dependencies:
                array_values($dependencies),
            cyclicrecommendationids:
                $cyclicids
        );
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     * @return array<int,CustomerSuccessDependency>
     */
    private function detect_dependencies(
        array $recommendations
    ): array {
        $dependencies = [];

        foreach ($recommendations as $candidate) {
            foreach (
                $recommendations
                as $possibledependency
            ) {
                if (
                    $candidate->recommendationid ===
                    $possibledependency->recommendationid
                ) {
                    continue;
                }

                foreach ($this->rules as $rule) {
                    $dependency = $rule->detect(
                        $candidate,
                        $possibledependency
                    );

                    if ($dependency === null) {
                        continue;
                    }

                    /*
                     * For version 7.8B, one direct dependency per action is
                     * retained. The earliest matching rule has precedence.
                     */
                    $dependencies[
                        $candidate->recommendationid
                    ] = $dependency;

                    break 2;
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param int[] $recommendationids
     * @param array<int,CustomerSuccessDependency> $dependencies
     * @return array{0:int[],1:int[]}
     */
    private function topological_sort(
        array $recommendationids,
        array $dependencies
    ): array {
        $indegree = array_fill_keys(
            $recommendationids,
            0
        );

        $dependants = [];

        foreach ($dependencies as $dependency) {
            if (
                !isset(
                    $indegree[
                        $dependency->recommendationid
                    ],
                    $indegree[
                        $dependency->dependsonrecommendationid
                    ]
                )
            ) {
                continue;
            }

            $indegree[
                $dependency->recommendationid
            ]++;

            $dependants[
                $dependency->dependsonrecommendationid
            ][] = $dependency->recommendationid;
        }

        $queue = [];

        foreach ($recommendationids as $recommendationid) {
            if ($indegree[$recommendationid] === 0) {
                $queue[] = $recommendationid;
            }
        }

        $ordered = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $ordered[] = $current;

            foreach (
                $dependants[$current] ?? []
                as $dependantid
            ) {
                $indegree[$dependantid]--;
            }
        }

        $cyclic = [];

        foreach ($recommendationids as $recommendationid) {
            if (
                !in_array(
                    $recommendationid,
                    $ordered,
                    true
                )
            ) {
                $cyclic[] = $recommendationid;
            }
        }

        return [
            $ordered,
            $cyclic,
        ];
    }
}