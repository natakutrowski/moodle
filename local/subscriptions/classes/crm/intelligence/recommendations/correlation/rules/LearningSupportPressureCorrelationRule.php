<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Correlates learning difficulty with an active support situation.
 */
final class LearningSupportPressureCorrelationRule extends
    AbstractCorrelationRule {

    public const KEY = 'learning_support_pressure';

    private const LEARNING_KEYS = [
        'learning.low_progress',
        'learning.minilesson_repeated_difficulty',
        'learning.readaloud_persistent_difficulty',
        'learning.wordcards_repeated_difficulty',
    ];

    private const SUPPORT_KEYS = [
        'support.pending_response',
        'support.stale_active_conversation',
        'support.unread_messages',
        'support.urgent_conversation',
    ];

    public function key(): string {
        return self::KEY;
    }

    public function match(
        CorrelationContext $context
    ): ?CorrelationMatch {
        $learning = $context->first_signal(
            self::LEARNING_KEYS
        );

        $support = $context->first_signal(
            self::SUPPORT_KEYS
        );

        if (
            $learning === null ||
            $support === null
        ) {
            return null;
        }

        $signals = [
            $learning,
            $support,
        ];

        $recommendations =
            $context->recommendations_by_keys([
                'review_learning_difficulty',
                'review_support_situation',
            ]);

        $urgent =
            $support->key ===
                'support.urgent_conversation' ||
            $support->key ===
                'support.stale_active_conversation';

        $confidence = $this->confidence(
            domaincount: 2,
            signalcount: count($signals),
            recommendationcount:
                count($recommendations),
            severitybonus: $urgent ? 10 : 5
        );

        $priority = min(
            100,
            max(
                $urgent ? 90 : 82,
                $confidence + 8
            )
        );

        $evidence =
            $this->evidence_from_signals(
                $signals
            );

        foreach ($recommendations as $item) {
            $evidence[] =
                $this->recommendation_evidence(
                    $item->key,
                    $item->priority,
                    $context->generatedat()
                );
        }

        $recommendation =
            new Recommendation(
                key:
                    'coordinate_learning_support_response',
                type:
                    Recommendation::PRESENTATION_ACTION,
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
                    RecommendationSource::INBOX,
                    RecommendationSource::MOODLE_LEARNING,
                ],
                createdat:
                    $context->generatedat(),
                validuntil:
                    $context->generatedat() +
                    (4 * DAYSECS)
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
                    $recommendations
                ),
            suppressedrecommendationkeys:
                $confidence >= 75
                    ? [
                        'review_learning_difficulty',
                        'review_support_situation',
                    ]
                    : [],
            metadata: [
                'urgent' => $urgent,
                'scenario' => self::KEY,
            ]
        );
    }
}