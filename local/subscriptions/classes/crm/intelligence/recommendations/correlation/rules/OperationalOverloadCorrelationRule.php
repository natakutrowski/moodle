<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Correlates active Inbox pressure with unresolved operational Work Items.
 */
final class OperationalOverloadCorrelationRule extends
    AbstractCorrelationRule {

    public const KEY = 'operational_overload';

    private const INBOX_KEYS = [
        'support.pending_response',
        'support.stale_active_conversation',
        'support.urgent_conversation',
        'support.unassigned_active_conversation',
    ];

    private const WORK_ITEM_KEYS = [
        'support.work_items_blocked',
        'support.work_items_overdue',
        'support.work_items_stale',
        'support.work_items_unassigned',
        'support.work_items_urgent',
    ];

    public function key(): string {
        return self::KEY;
    }

    public function match(
        CorrelationContext $context
    ): ?CorrelationMatch {
        $inbox = $context->first_signal(
            self::INBOX_KEYS
        );

        $workitem = $context->first_signal(
            self::WORK_ITEM_KEYS
        );

        if (
            $inbox === null ||
            $workitem === null
        ) {
            return null;
        }

        $signals = [
            $inbox,
            $workitem,
        ];

        $recommendations =
            $context->recommendations_by_keys([
                'review_support_situation',
                'review_active_work_items',
            ]);

        $urgent =
            in_array(
                $inbox->key,
                [
                    'support.urgent_conversation',
                    'support.stale_active_conversation',
                ],
                true
            ) ||
            in_array(
                $workitem->key,
                [
                    'support.work_items_blocked',
                    'support.work_items_overdue',
                    'support.work_items_urgent',
                ],
                true
            );

        $confidence = $this->confidence(
            domaincount: 2,
            signalcount: 2,
            recommendationcount:
                count($recommendations),
            severitybonus: $urgent ? 10 : 5
        );

        $priority = min(
            100,
            max(
                $urgent ? 91 : 82,
                $confidence + 7
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
                    'coordinate_operational_overload',
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
                    RecommendationSource::WORK_MANAGEMENT,
                ],
                createdat:
                    $context->generatedat(),
                validuntil:
                    $context->generatedat() +
                    (3 * DAYSECS)
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
                        'review_support_situation',
                        'review_active_work_items',
                    ]
                    : [],
            metadata: [
                'urgent' => $urgent,
                'scenario' => self::KEY,
            ]
        );
    }
}