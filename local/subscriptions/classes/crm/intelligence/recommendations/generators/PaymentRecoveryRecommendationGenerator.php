<?php

namespace local_subscriptions\crm\intelligence\recommendations\generators;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationAction;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationResult;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;

/**
 * Detects pending or recently failed payment situations.
 */
final class PaymentRecoveryRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'payment_recovery';

    private const PAYMENT_KEYS = [
        'commercial.pending_payments',
        'commercial.recent_failed_payments',
    ];

    public function key(): string {
        return self::KEY;
    }

    public function generate(
        RecommendationGenerationContext $context
    ): RecommendationGenerationResult {
        $result = $this->success_result($context);

        if ($result === null) {
            return RecommendationGenerationResult::skipped(
                $this->key(),
                'customer_success_context_unavailable'
            );
        }

        $signals = $this->signals_by_keys(
            $result,
            self::PAYMENT_KEYS
        );

        if ($signals === []) {
            return RecommendationGenerationResult::success(
                $this->key(),
                [],
                $this->result_metadata(
                    $result,
                    []
                )
            );
        }

        $signals = $this->strongest_signals(
            $signals,
            4
        );

        $hasfailedpayments = $this->contains_key(
            $signals,
            'commercial.recent_failed_payments'
        );

        $priority = $this->priority_from_signals(
            $signals,
            $hasfailedpayments ? 85 : 72
        );

        $actionparams = [
            'emailpurpose' => 'payment_follow_up',
        ];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'review_blocked_payment',
            type: Recommendation::PRESENTATION_ACTION,
            priority: $priority,
            action: new RecommendationAction(
                key: 'prepare_payment_follow_up',
                action:
                    RecommendationAction::PREPARE_EMAIL,
                params: $actionparams,
                primary: true,
                confirmationrequired: true
            ),
            recommendationtype:
                RecommendationType::PAYMENT_RECOVERY,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals($signals),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
                RecommendationSource::PAYMENTS,
                RecommendationSource::SUBSCRIPTIONS,
            ],
            createdat: $context->timestamp(),
            validuntil:
                $context->timestamp() + (5 * DAYSECS)
        );

        return RecommendationGenerationResult::success(
            $this->key(),
            [$recommendation],
            $this->result_metadata(
                $result,
                $signals
            )
        );
    }

    private function contains_key(
        array $signals,
        string $key
    ): bool {
        foreach ($signals as $signal) {
            if ($signal->key === $key) {
                return true;
            }
        }

        return false;
    }
}