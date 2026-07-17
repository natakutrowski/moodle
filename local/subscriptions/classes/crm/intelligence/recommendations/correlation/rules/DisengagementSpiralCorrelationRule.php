<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Correlates inactivity, low progress and reduced gamification activity.
 */
final class DisengagementSpiralCorrelationRule extends
    AbstractCorrelationRule {

    public const KEY = 'disengagement_spiral';

    private const INACTIVITY_KEYS = [
        'activity.inactive_14d',
        'activity.inactive_30d',
        'activity.never_accessed',
    ];

    private const LEARNING_KEYS = [
        'learning.low_progress',
        'learning.not_started',
    ];

    private const GAMIFICATION_KEYS = [
        'gamification.no_recent_xp',
    ];

    public function key(): string {
        return self::KEY;
    }

    public function match(
        CorrelationContext $context
    ): ?CorrelationMatch {
        $inactivity = $context->first_signal(
            self::INACTIVITY_KEYS
        );

        $learning = $context->first_signal(
            self::LEARNING_KEYS
        );

        if (
            $inactivity === null ||
            $learning === null
        ) {
            return null;
        }

        $signals = [
            $inactivity,
            $learning,
        ];

        $gamification =
            $context->first_signal(
                self::GAMIFICATION_KEYS
            );

        if ($gamification !== null) {
            $signals[] = $gamification;
        }

        $supportingrecommendations =
            $context->recommendations_by_keys([
                'review_customer_success_risk',
                'review_learning_difficulty',
            ]);

        $severitybonus =
            $inactivity->key ===
                'activity.inactive_30d' ||
            $inactivity->key ===
                'activity.never_accessed'
                ? 10
                : 4;

        $confidence = $this->confidence(
            domaincount:
                $gamification !== null ? 3 : 2,
            signalcount: count($signals),
            recommendationcount:
                count($supportingrecommendations),
            severitybonus: $severitybonus
        );

        $priority = min(
            100,
            max(82, $confidence + 5)
        );

        $evidence =
            $this->evidence_from_signals(
                $signals
            );

        foreach (
            $supportingrecommendations
            as $recommendation
        ) {
            $evidence[] =
                $this->recommendation_evidence(
                    $recommendation->key,
                    $recommendation->priority,
                    $context->generatedat()
                );
        }

        $recommendation =
            new Recommendation(
                key:
                    'intervene_disengagement_spiral',
                type:
                    Recommendation::PRESENTATION_WARNING,
                priority: $priority,
                action:
                    $this->coordinated_action(
                        $context,
                        self::KEY,
                        $priority >= 90
                            ? 'urgent'
                            : 'high'
                    ),
                recommendationtype:
                    RecommendationType::CROSS_DOMAIN_INTERVENTION,
                target:
                    $context->generationcontext
                        ->user_target(),
                evidence: $evidence,
                sources: [
                    RecommendationSource::CORRELATION_ENGINE,
                    RecommendationSource::CUSTOMER_SUCCESS,
                    RecommendationSource::MOODLE_ACTIVITY,
                    RecommendationSource::MOODLE_LEARNING,
                ],
                createdat:
                    $context->generatedat(),
                validuntil:
                    $context->generatedat() +
                    (7 * DAYSECS)
            );

        return new CorrelationMatch(
            rulekey: $this->key(),
            recommendation: $recommendation,
            confidencescore: $confidence,
            matchedsignalkeys:
                $this->signal_keys($signals),
            matchedrecommendationkeys:
                array_map(
                    static fn(
                        Recommendation $item
                    ): string => $item->key,
                    $supportingrecommendations
                ),
            suppressedrecommendationkeys:
                $confidence >= 75
                    ? [
                        'review_customer_success_risk',
                        'review_learning_difficulty',
                    ]
                    : [],
            metadata: [
                'domaincount' =>
                    $gamification !== null ? 3 : 2,
                'scenario' => self::KEY,
            ]
        );
    }
}