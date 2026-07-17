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

/**
 * Detects learning difficulties and abnormal inactivity.
 */
final class LearningSupportRecommendationGenerator extends
    AbstractCustomerSuccessRecommendationGenerator {

    public const KEY = 'learning_support';

    private const ACTIONABLE_KEYS = [
        'learning.low_progress',
        'learning.not_started',
        'learning.minilesson_repeated_difficulty',
        'learning.readaloud_persistent_difficulty',
        'learning.wordcards_repeated_difficulty',
        'activity.inactive_14d',
        'activity.inactive_30d',
        'activity.never_accessed',
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

        $signals = $this->negative_signals(
            array_merge(
                $this->signals_by_categories(
                    $result,
                    [
                        SuccessSignalCategory::LEARNING,
                        SuccessSignalCategory::ENGAGEMENT,
                    ]
                ),
                $this->signals_by_keys(
                    $result,
                    self::ACTIONABLE_KEYS
                )
            )
        );

        $signals = $this->deduplicate_signals(
            $signals
        );

        $actionablesignals = array_values(
            array_filter(
                $signals,
                static fn($signal): bool =>
                    in_array(
                        $signal->key,
                        self::ACTIONABLE_KEYS,
                        true
                    ) ||
                    abs($signal->weight) >= 40
            )
        );

        if ($actionablesignals === []) {
            return RecommendationGenerationResult::success(
                $this->key(),
                [],
                $this->result_metadata(
                    $result,
                    $signals
                )
            );
        }

        $actionablesignals =
            $this->strongest_signals(
                $actionablesignals,
                5
            );

        $priority = $this->priority_from_signals(
            $actionablesignals,
            65
        );

        $actionparams = [
            'workitemtype' => 'student_follow_up',
            'source' => 'recommendation',
            'suggestedpriority' =>
                $priority >= 80 ? 'high' : 'normal',
        ];

        if ($context->resolved_userid() !== null) {
            $actionparams['userid'] =
                $context->resolved_userid();
        }

        $recommendation = new Recommendation(
            key: 'review_learning_difficulty',
            type: Recommendation::PRESENTATION_ACTION,
            priority: $priority,
            action: new RecommendationAction(
                key: 'propose_learning_follow_up',
                action:
                    RecommendationAction::PROPOSE_WORK_ITEM,
                params: $actionparams,
                primary: true,
                confirmationrequired: true
            ),
            recommendationtype:
                RecommendationType::LEARNING_SUPPORT,
            target: $context->user_target(),
            evidence:
                $this->evidence_from_signals(
                    $actionablesignals
                ),
            sources: [
                RecommendationSource::CUSTOMER_SUCCESS,
                RecommendationSource::MOODLE_ACTIVITY,
                RecommendationSource::MOODLE_LEARNING,
            ],
            createdat: $context->timestamp(),
            validuntil:
                $context->timestamp() + (10 * DAYSECS)
        );

        return RecommendationGenerationResult::success(
            $this->key(),
            [$recommendation],
            $this->result_metadata(
                $result,
                $actionablesignals
            )
        );
    }

    /**
     * @return array
     */
    private function deduplicate_signals(
        array $signals
    ): array {
        $indexed = [];

        foreach ($signals as $signal) {
            $indexed[$signal->identity()] = $signal;
        }

        return array_values($indexed);
    }
}