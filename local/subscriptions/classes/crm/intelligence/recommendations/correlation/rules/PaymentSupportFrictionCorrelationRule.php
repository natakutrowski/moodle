<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Correlates payment failure with an Inbox support situation.
 */
final class PaymentSupportFrictionCorrelationRule extends
    AbstractCorrelationRule {

    public const KEY = 'payment_support_friction';

    private const PAYMENT_KEYS = [
        'commercial.recent_failed_payments',
        'commercial.pending_payments',
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
        $payment = $context->first_signal(
            self::PAYMENT_KEYS
        );

        $support = $context->first_signal(
            self::SUPPORT_KEYS
        );

        if (
            $payment === null ||
            $support === null
        ) {
            return null;
        }

        $signals = [
            $payment,
            $support,
        ];

        $recommendations =
            $context->recommendations_by_keys([
                'review_blocked_payment',
                'review_support_situation',
            ]);

        $failedpayment =
            $payment->key ===
            'commercial.recent_failed_payments';

        $urgentconversation =
            $support->key ===
            'support.urgent_conversation';

        $confidence = $this->confidence(
            domaincount: 2,
            signalcount: 2,
            recommendationcount:
                count($recommendations),
            severitybonus:
                ($failedpayment ? 5 : 0) +
                ($urgentconversation ? 5 : 0)
        );

        $priority = min(
            100,
            max(
                $failedpayment ||
                $urgentconversation
                    ? 92
                    : 84,
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
                    'coordinate_payment_support_resolution',
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
                    RecommendationSource::PAYMENTS,
                    RecommendationSource::SUBSCRIPTIONS,
                    RecommendationSource::INBOX,
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
                        'review_blocked_payment',
                        'review_support_situation',
                    ]
                    : [],
            metadata: [
                'failedpayment' =>
                    $failedpayment,
                'urgentconversation' =>
                    $urgentconversation,
                'scenario' => self::KEY,
            ]
        );
    }
}