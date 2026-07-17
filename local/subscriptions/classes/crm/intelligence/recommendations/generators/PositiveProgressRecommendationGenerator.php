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
 * Detects meaningful positive learning progress.
 *
 * Positive recommendations are deliberately lower priority than operational
 * risks and are only generated when several strong signals corroborate.
 */
final class PositiveProgressRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'positive_progress';

    private const POSITIVE_KEYS = [
        'learning.course_completed',
        'learning.high_progress',
        'learning.good_progress',
        'learning.minilesson_high_performance',
        'learning.readaloud_high_accuracy',
        'learning.solo_strong_accuracy',
        'learning.wordcards_high_success_rate',
        'learning.wordcards_vocabulary_growth',
        'gamification.regular_xp_activity',
        'activity.strong_regularity',
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

        $signals = $this->positive_signals(
            $this->signals_by_keys(
                $result,
                self::POSITIVE_KEYS
            )
        );

        $strongsignals = array_values(
            array_filter(
                $signals,
                static fn($signal): bool =>
                    $signal->weight >= 25
            )
        );

        /*
         * Avoid noisy congratulations based on one isolated signal.
         */
        if (count($strongsignals) < 2) {
            return RecommendationGenerationResult::success(
                $this->key(),
                [],
                $this->result_metadata(
                    $result,
                    $signals
                )
            );
        }

        $strongsignals =
            $this->strongest_signals(
                $strongsignals,
                5
            );

        $priority = min(
            60,
            $this->priority_from_signals(
                $strongsignals,
                35
            )
        );

        $actionparams = [
            'emailpurpose' => 'positive_progress',
        ];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'recognise_positive_progress',
            type: Recommendation::PRESENTATION_INFO,
            priority: $priority,
            action: new RecommendationAction(
                key: 'prepare_progress_message',
                action:
                    RecommendationAction::PREPARE_EMAIL,
                params: $actionparams,
                primary: true,
                confirmationrequired: true
            ),
            recommendationtype:
                RecommendationType::POSITIVE_PROGRESS,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals(
                    $strongsignals
                ),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
                RecommendationSource::MOODLE_ACTIVITY,
                RecommendationSource::MOODLE_LEARNING,
            ],
            createdat: $context->timestamp(),
            validuntil:
                $context->timestamp() + (14 * DAYSECS)
        );

        return RecommendationGenerationResult::success(
            $this->key(),
            [$recommendation],
            $this->result_metadata(
                $result,
                $strongsignals
            )
        );
    }
}