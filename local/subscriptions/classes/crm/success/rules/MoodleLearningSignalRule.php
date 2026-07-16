<?php

namespace local_subscriptions\crm\success\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessSignalRuleInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\domain\SuccessSignalPolarity;
use local_subscriptions\crm\success\signals\SuccessSignal;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Converts native course completion metrics into learning signals.
 */
final class MoodleLearningSignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'moodle_learning_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return $metrics->has(
            SuccessMetricSource::COURSE_COMPLETION,
            'learning.accessible_course_count'
        );
    }

    public function evaluate(
        SuccessMetricCollection $metrics,
        int $detectedat
    ): SuccessSignalCollection {
        $userid = $metrics->userid();

        if ($userid === null) {
            return new SuccessSignalCollection();
        }

        $signals = new SuccessSignalCollection();

        $accessibleCourses = $this->integer_value(
            $metrics,
            'learning.accessible_course_count'
        );

        $trackedCourses = $this->integer_value(
            $metrics,
            'learning.tracked_course_count'
        );

        $startedCourses = $this->integer_value(
            $metrics,
            'learning.started_course_count'
        );

        $completedCourses = $this->integer_value(
            $metrics,
            'learning.completed_course_count'
        );

        $trackedActivities = $this->integer_value(
            $metrics,
            'learning.tracked_activity_count'
        );

        $completedActivities = $this->integer_value(
            $metrics,
            'learning.completed_activity_count'
        );

        $overallProgress = $this->nullable_float_value(
            $metrics,
            'learning.overall_activity_progress_percentage'
        );

        if (
            $accessibleCourses > 0 &&
            $trackedCourses === 0
        ) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.completion_not_configured',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::NEUTRAL,
                    0,
                    $accessibleCourses,
                    [
                        $this->identity(
                            'learning.accessible_course_count'
                        ),
                        $this->identity(
                            'learning.tracked_course_count'
                        ),
                    ],
                    $detectedat,
                    [
                        'accessiblecourses' =>
                            $accessibleCourses,
                    ]
                )
            );

            return $signals;
        }

        if (
            $trackedCourses > 0 &&
            $startedCourses === 0
        ) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.not_started',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::NEGATIVE,
                    -20,
                    0,
                    [
                        $this->identity(
                            'learning.started_course_count'
                        ),
                        $this->identity(
                            'learning.tracked_course_count'
                        ),
                    ],
                    $detectedat,
                    [
                        'trackedcourses' =>
                            $trackedCourses,
                    ]
                )
            );
        }

        if ($overallProgress !== null) {
            $this->add_progress_signal(
                $signals,
                $userid,
                $overallProgress,
                $detectedat
            );
        }

        if ($completedCourses > 0) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.course_completed',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::POSITIVE,
                    min(25, 15 + (($completedCourses - 1) * 5)),
                    $completedCourses,
                    [
                        $this->identity(
                            'learning.completed_course_count'
                        ),
                    ],
                    $detectedat,
                    [
                        'completedcourses' =>
                            $completedCourses,
                    ]
                )
            );
        }

        if (
            $trackedActivities >= 10 &&
            $completedActivities >= 5
        ) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.meaningful_activity_completion',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::POSITIVE,
                    8,
                    $completedActivities,
                    [
                        $this->identity(
                            'learning.completed_activity_count'
                        ),
                        $this->identity(
                            'learning.tracked_activity_count'
                        ),
                    ],
                    $detectedat,
                    [
                        'completedactivities' =>
                            $completedActivities,
                        'trackedactivities' =>
                            $trackedActivities,
                    ]
                )
            );
        }

        return $signals;
    }

    private function add_progress_signal(
        SuccessSignalCollection $signals,
        int $userid,
        float $progress,
        int $detectedat
    ): void {
        $identity = $this->identity(
            'learning.overall_activity_progress_percentage'
        );

        if ($progress >= 80) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.high_progress',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::POSITIVE,
                    25,
                    $progress,
                    [$identity],
                    $detectedat
                )
            );

            return;
        }

        if ($progress >= 50) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.good_progress',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::POSITIVE,
                    16,
                    $progress,
                    [$identity],
                    $detectedat
                )
            );

            return;
        }

        if ($progress >= 20) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.started_progress',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::POSITIVE,
                    7,
                    $progress,
                    [$identity],
                    $detectedat
                )
            );

            return;
        }

        if ($progress > 0) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'learning.low_progress',
                    SuccessSignalCategory::LEARNING,
                    SuccessSignalPolarity::NEGATIVE,
                    -8,
                    $progress,
                    [$identity],
                    $detectedat
                )
            );
        }
    }

    private function integer_value(
        SuccessMetricCollection $metrics,
        string $key
    ): int {
        $metric = $metrics->get(
            SuccessMetricSource::COURSE_COMPLETION,
            $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function nullable_float_value(
        SuccessMetricCollection $metrics,
        string $key
    ): ?float {
        $metric = $metrics->get(
            SuccessMetricSource::COURSE_COMPLETION,
            $key
        );

        if (
            $metric === null ||
            $metric->value === null
        ) {
            return null;
        }

        return (float)$metric->value;
    }

    private function identity(
        string $key
    ): string {
        return
            SuccessMetricSource::COURSE_COMPLETION .
            ':' .
            $key;
    }
}