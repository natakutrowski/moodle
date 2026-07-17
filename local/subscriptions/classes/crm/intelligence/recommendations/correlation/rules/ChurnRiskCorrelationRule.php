<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationContext;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationMatch;

/**
 * Detects a high-confidence churn risk across loyalty, activity and friction.
 */
final class ChurnRiskCorrelationRule extends
    AbstractCorrelationRule {

    public const KEY = 'cross_domain_churn_risk';

    private const ACCESS_KEYS = [
        'loyalty.no_current_access',
    ];

    private const INACTIVITY_KEYS = [
        'activity.inactive_14d',
        'activity.inactive_30d',
    ];

    private const FRICTION_KEYS = [
        'commercial.recent_failed_payments',
        'commercial.pending_payments',
        'support.pending_response',
        'support.stale_active_conversation',
        'support.urgent_conversation',
    ];

    public function key(): string {
        return self::KEY;
    }

    public function match(
        CorrelationContext $context
    ): ?CorrelationMatch {
        $access = $context->first_signal(
            self::ACCESS_KEYS
        );

        $inactivity = $context->first_signal(
            self::INACTIVITY_KEYS
        );

        if (
            $access === null ||
            $inactivity === null
        ) {
            return null;
        }

        $frictionsignals =
            $context->signals(
                self::FRICTION_KEYS
            );

        /*
         * No-current-access plus inactivity is not sufficient alone:
         * one additional friction signal is required.
         */
        if ($frictionsignals === []) {
            return null;
        }

        $signals = array_merge(
            [
                $access,
                $inactivity,
            ],
            $frictionsignals
        );

        $recommendations =
            $context->recommendations_by_keys([
                'review_customer_success_risk',
                'review_blocked_payment',
                'review_support_situation',
            ]);

        $domaincount = 2;

        if (
            $context->has_signal(
                'commercial.recent_failed_payments'
            ) ||
            $context->has_signal(
                'commercial.pending_payments'
            )
        ) {
            $domaincount++;
        }

        if (
            $context->has_signal(
                'support.pending_response'
            ) ||
            $context->has_signal(
                'support.stale_active_conversation'
            ) ||
            $context->has_signal(
                'support.urgent_conversation'
            )
        ) {
            $domaincount++;
        }

        $confidence = $this->confidence(
            domaincount: $domaincount,
            signalcount: count($signals),
            recommendationcount:
                count($recommendations),
            severitybonus:
                $inactivity->key ===
                    'activity.inactive_30d'
                    ? 10
                    : 5
        );

        $priority = min(
            100,
            max(94, $confidence + 5)
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
                    'intervene_cross_domain_churn_risk',
                type:
                    Recommendation::PRESENTATION_WARNING,
                priority: $priority,
                action:
                    $this->coordinated_action(
                        $context,
                        self::KEY,
                        'urgent'
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
                    RecommendationSource::SUBSCRIPTIONS,
                    RecommendationSource::PAYMENTS,
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
            suppressedrecommendationkeys: [
                'review_customer_success_risk',
                'review_blocked_payment',
                'review_support_situation',
            ],
            metadata: [
                'domaincount' => $domaincount,
                'frictionsignalcount' =>
                    count($frictionsignals),
                'scenario' => self::KEY,
            ]
        );
    }
}