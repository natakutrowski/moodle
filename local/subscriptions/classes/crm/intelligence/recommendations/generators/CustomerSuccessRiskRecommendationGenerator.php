<?php

namespace local_subscriptions\crm\intelligence\recommendations\generators;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationAction;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationResult;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\scoring\SuccessHealthLevel;

/**
 * Detects transversal Customer Success risk requiring human review.
 */
final class CustomerSuccessRiskRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'customer_success_risk';

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

        if (!$result->has_data()) {
            return RecommendationGenerationResult::skipped(
                $this->key(),
                'customer_success_data_unavailable'
            );
        }

        $risksignals = $this->negative_signals(
            $this->signals_by_categories(
                $result,
                [
                    SuccessSignalCategory::RISK,
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalCategory::SUPPORT,
                    SuccessSignalCategory::LOYALTY,
                ]
            )
        );

        $riskexposure =
            $result->score->risk_exposure();

        $healthrequiresreview = in_array(
            $result->score->level,
            [
                SuccessHealthLevel::AT_RISK,
                SuccessHealthLevel::CRITICAL,
            ],
            true
        );

        $exposurerequiresreview =
            $riskexposure !== null &&
            $riskexposure >= 60;

        if (
            !$healthrequiresreview &&
            !$exposurerequiresreview &&
            count($risksignals) < 2
        ) {
            return RecommendationGenerationResult::success(
                $this->key(),
                [],
                $this->result_metadata(
                    $result,
                    $risksignals
                )
            );
        }

        $risksignals = $this->strongest_signals(
            $risksignals,
            6
        );

        $priority = $this->priority_from_signals(
            $risksignals,
            $result->score->level ===
                SuccessHealthLevel::CRITICAL
                ? 95
                : 80
        );

        if ($riskexposure !== null) {
            $priority = max(
                $priority,
                min(100, $riskexposure)
            );
        }

        $actionparams = [];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'review_customer_success_risk',
            type: Recommendation::PRESENTATION_WARNING,
            priority: $priority,
            action: new RecommendationAction(
                key: 'review_customer_success_risk',
                action: RecommendationAction::OPEN_USER_PROFILE,
                params: $actionparams,
                primary: true
            ),
            recommendationtype:
                RecommendationType::RISK_REVIEW,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals(
                    $risksignals
                ),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
            ],
            createdat: $context->timestamp(),
            validuntil:
                $context->timestamp() + (7 * DAYSECS)
        );

        return RecommendationGenerationResult::success(
            $this->key(),
            [$recommendation],
            $this->result_metadata(
                $result,
                $risksignals
            ) + [
                'riskexposure' => $riskexposure,
            ]
        );
    }
}