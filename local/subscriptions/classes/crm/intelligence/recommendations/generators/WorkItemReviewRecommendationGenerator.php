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
 * Detects blocked, overdue, stale or unassigned Work Items.
 */
final class WorkItemReviewRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'work_item_review';

    private const ACTIONABLE_KEYS = [
        'support.work_items_blocked',
        'support.work_items_overdue',
        'support.work_items_stale',
        'support.work_items_unassigned',
        'support.work_items_urgent',
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
            $this->contains_urgent_signal($signals)
                ? 90
                : 70
        );

        $actionparams = [];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'review_active_work_items',
            type: Recommendation::PRESENTATION_ACTION,
            priority: $priority,
            action: new RecommendationAction(
                key: 'open_user_work_items',
                action:
                    RecommendationAction::OPEN_USER_PROFILE,
                params: $actionparams,
                primary: true
            ),
            recommendationtype:
                RecommendationType::WORK_ITEM_REVIEW,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals($signals),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
                RecommendationSource::WORK_MANAGEMENT,
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

    private function contains_urgent_signal(
        array $signals
    ): bool {
        foreach ($signals as $signal) {
            if (
                in_array(
                    $signal->key,
                    [
                        'support.work_items_urgent',
                        'support.work_items_overdue',
                        'support.work_items_blocked',
                    ],
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }
}