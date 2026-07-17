<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationAction;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEvidence;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\success\signals\SuccessSignal;

/**
 * Shared helpers used by cross-domain correlation rules.
 */
abstract class AbstractCorrelationRule implements
    CorrelationRuleInterface {

    /**
     * @param SuccessSignal[] $signals
     * @return RecommendationEvidence[]
     */
    final protected function evidence_from_signals(
        array $signals
    ): array {
        $evidence = [];

        foreach ($signals as $signal) {
            if (!$signal instanceof SuccessSignal) {
                throw new \InvalidArgumentException(
                    'Correlation evidence requires SuccessSignal objects.'
                );
            }

            $evidence[] = new RecommendationEvidence(
                source:
                    RecommendationSource::CUSTOMER_SUCCESS,
                key: $signal->key,
                value: $signal->value,
                weight: min(
                    100,
                    abs($signal->weight)
                ),
                detectedat: $signal->detectedat,
                context: [
                    'category' => $signal->category,
                    'polarity' => $signal->polarity,
                    'signalweight' =>
                        $signal->weight,
                    'metricidentities' =>
                        $signal->metricidentities,
                    'signalcontext' =>
                        $signal->context,
                ]
            );
        }

        return $evidence;
    }

    /**
     * Add evidence describing an already generated recommendation.
     */
    final protected function recommendation_evidence(
        string $recommendationkey,
        int $priority,
        int $detectedat
    ): RecommendationEvidence {
        return new RecommendationEvidence(
            source:
                RecommendationSource::CORRELATION_ENGINE,
            key:
                'recommendation.' .
                $recommendationkey,
            value: true,
            weight: min(100, max(0, $priority)),
            detectedat: $detectedat,
            context: [
                'recommendationkey' =>
                    $recommendationkey,
                'recommendationpriority' =>
                    $priority,
            ]
        );
    }

    /**
     * Build the default human action proposed by correlations.
     */
    final protected function coordinated_action(
        CorrelationContext $context,
        string $scenario,
        string $suggestedpriority
    ): RecommendationAction {
        $params = [
            'scenario' => $scenario,
            'suggestedpriority' =>
                $suggestedpriority,
            'source' => 'correlation_engine',
        ];

        if ($context->userid() !== null) {
            $params['userid'] =
                $context->userid();
        }

        return new RecommendationAction(
            key:
                'coordinate_' . $scenario,
            action:
                RecommendationAction::PREPARE_COORDINATED_FOLLOW_UP,
            params: $params,
            primary: true,
            confirmationrequired: true
        );
    }

    /**
     * Calculate confidence using independent domains and corroborating facts.
     */
    final protected function confidence(
        int $domaincount,
        int $signalcount,
        int $recommendationcount = 0,
        int $severitybonus = 0
    ): int {
        $score = 30;

        $score += min(
            30,
            max(0, $domaincount - 1) * 15
        );

        $score += min(
            20,
            max(0, $signalcount - 2) * 5
        );

        $score += min(
            10,
            $recommendationcount * 5
        );

        $score += min(
            10,
            max(0, $severitybonus)
        );

        return min(100, max(0, $score));
    }

    /**
     * @param SuccessSignal[] $signals
     * @return string[]
     */
    final protected function signal_keys(
        array $signals
    ): array {
        return array_values(array_map(
            static fn(
                SuccessSignal $signal
            ): string => $signal->key,
            $signals
        ));
    }

    /**
     * Merge evidence while preserving stable identities.
     *
     * @param RecommendationEvidence[] ...$collections
     * @return RecommendationEvidence[]
     */
    final protected function merge_evidence(
        array ...$collections
    ): array {
        $indexed = [];

        foreach ($collections as $collection) {
            foreach ($collection as $evidence) {
                if (
                    !$evidence instanceof
                    RecommendationEvidence
                ) {
                    throw new \InvalidArgumentException(
                        'Correlation evidence collection contains an invalid object.'
                    );
                }

                $indexed[$evidence->identity()] =
                    $evidence;
            }
        }

        return array_values($indexed);
    }
}