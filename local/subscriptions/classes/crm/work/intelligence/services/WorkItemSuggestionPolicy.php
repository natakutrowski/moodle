<?php

namespace local_subscriptions\crm\work\intelligence\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\dto\AssistantRecommendation;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemType;

/**
 * Maps recommendation scenarios to Work Item characteristics.
 */
final class WorkItemSuggestionPolicy {

    public function type(
        AssistantRecommendation $recommendation
    ): string {
        return match ($recommendation->key) {
            'review_blocked_payment',
            'coordinate_payment_support_resolution' =>
                WorkItemType::FINANCE,

            'review_support_situation',
            'coordinate_operational_overload' =>
                WorkItemType::SUPPORT,

            'review_learning_difficulty',
            'coordinate_learning_support_response',
            'intervene_disengagement_spiral',
            'intervene_cross_domain_churn_risk' =>
                WorkItemType::FOLLOW_UP,

            'review_active_work_items' =>
                WorkItemType::TASK,

            default => match (
                $recommendation->type
            ) {
                'payment_recovery' =>
                    WorkItemType::FINANCE,

                'support_follow_up' =>
                    WorkItemType::SUPPORT,

                'learning_support',
                'risk_review',
                'cross_domain_intervention' =>
                    WorkItemType::FOLLOW_UP,

                'work_item_review' =>
                    WorkItemType::TASK,

                default =>
                    WorkItemType::TASK,
            },
        };
    }

    public function priority(
        AssistantRecommendation $recommendation
    ): string {
        return match (
            $recommendation->prioritylevel
        ) {
            'critical' =>
                WorkItemPriority::CRITICAL,

            'urgent' =>
                WorkItemPriority::URGENT,

            'high' =>
                WorkItemPriority::HIGH,

            'low' =>
                WorkItemPriority::LOW,

            default =>
                WorkItemPriority::NORMAL,
        };
    }

    public function dueat(
        AssistantRecommendation $recommendation,
        int $now
    ): ?int {
        return match (
            $recommendation->prioritylevel
        ) {
            'critical' =>
                $now + DAYSECS,

            'urgent' =>
                $now + (2 * DAYSECS),

            'high' =>
                $now + (5 * DAYSECS),

            'normal' =>
                $now + (10 * DAYSECS),

            'low' =>
                $now + (20 * DAYSECS),

            default => null,
        };
    }

    public function confidence(
        AssistantRecommendation $recommendation,
        bool $teamavailable,
        bool $duplicatefound
    ): int {
        $score = 50;

        $score += min(
            30,
            (int)round(
                $recommendation->priority / 4
            )
        );

        if ($recommendation->evidence_count() >= 2) {
            $score += 5;
        }

        if ($recommendation->source_count() >= 2) {
            $score += 5;
        }

        if ($teamavailable) {
            $score += 5;
        }

        if ($duplicatefound) {
            $score -= 25;
        }

        return max(0, min(100, $score));
    }
}