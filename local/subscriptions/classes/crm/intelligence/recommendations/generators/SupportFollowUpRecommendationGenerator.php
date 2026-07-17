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
 * Detects Inbox situations requiring human support follow-up.
 */
final class SupportFollowUpRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'support_follow_up';

    private const ACTIONABLE_KEYS = [
        'support.pending_response',
        'support.stale_active_conversation',
        'support.unassigned_active_conversation',
        'support.unread_messages',
        'support.urgent_conversation',
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
            self::ACTIONABLE_KEYS
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
            5
        );

        $priority = $this->priority_from_signals(
            $signals,
            $this->contains_key(
                $signals,
                'support.urgent_conversation'
            ) ? 90 : 70
        );

        $actionparams = [];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'review_support_situation',
            type: Recommendation::PRESENTATION_ACTION,
            priority: $priority,
            action: new RecommendationAction(
                key: 'open_support_user_profile',
                action:
                    RecommendationAction::OPEN_USER_PROFILE,
                params: $actionparams,
                primary: true
            ),
            recommendationtype:
                RecommendationType::SUPPORT_FOLLOW_UP,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals($signals),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
                RecommendationSource::INBOX,
            ],
            createdat: $context->timestamp(),
            validuntil:
                $context->timestamp() + (3 * DAYSECS)
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