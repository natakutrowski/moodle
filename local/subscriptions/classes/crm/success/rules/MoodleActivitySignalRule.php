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
 * Converts Moodle activity metrics into engagement signals.
 */
final class MoodleActivitySignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'moodle_activity_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return
            $metrics->has(
                SuccessMetricSource::MOODLE_USER,
                'activity.last_access_at'
            ) ||
            $metrics->has(
                SuccessMetricSource::MOODLE_LOGS,
                'activity.active_days_30d'
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

        $lastaccess = $this->integer_value(
            $metrics->get(
                SuccessMetricSource::MOODLE_USER,
                'activity.last_access_at'
            )
        );

        $activeDays7d = $this->integer_value(
            $metrics->get(
                SuccessMetricSource::MOODLE_LOGS,
                'activity.active_days_7d'
            )
        );

        $activeDays30d = $this->integer_value(
            $metrics->get(
                SuccessMetricSource::MOODLE_LOGS,
                'activity.active_days_30d'
            )
        );

        $sessions30d = $this->integer_value(
            $metrics->get(
                SuccessMetricSource::MOODLE_LOGS,
                'activity.sessions_30d'
            )
        );

        $uniqueTargets30d = $this->integer_value(
            $metrics->get(
                SuccessMetricSource::MOODLE_LOGS,
                'activity.unique_navigation_targets_30d'
            )
        );

        $this->add_recency_signal(
            $signals,
            $userid,
            $lastaccess,
            $detectedat
        );

        $this->add_regularity_signal(
            $signals,
            $userid,
            $activeDays7d,
            $activeDays30d,
            $detectedat
        );

        if ($sessions30d >= 12) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.frequent_sessions_30d',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::POSITIVE,
                    8,
                    $sessions30d,
                    [
                        SuccessMetricSource::MOODLE_LOGS .
                        ':activity.sessions_30d',
                    ],
                    $detectedat,
                    [
                        'sessions30d' => $sessions30d,
                    ]
                )
            );
        }

        if ($uniqueTargets30d >= 15) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.diverse_navigation_30d',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::POSITIVE,
                    5,
                    $uniqueTargets30d,
                    [
                        SuccessMetricSource::MOODLE_LOGS .
                        ':activity.unique_navigation_targets_30d',
                    ],
                    $detectedat,
                    [
                        'uniquetargets30d' =>
                            $uniqueTargets30d,
                    ]
                )
            );
        }

        return $signals;
    }

    private function add_recency_signal(
        SuccessSignalCollection $signals,
        int $userid,
        int $lastaccess,
        int $detectedat
    ): void {
        $identity =
            SuccessMetricSource::MOODLE_USER .
            ':activity.last_access_at';

        if ($lastaccess <= 0) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.never_accessed',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::NEGATIVE,
                    -35,
                    0,
                    [$identity],
                    $detectedat
                )
            );

            return;
        }

        $dayssince = max(
            0,
            (int)floor(
                ($detectedat - $lastaccess) /
                DAYSECS
            )
        );

        if ($dayssince <= 1) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.recent_access',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::POSITIVE,
                    12,
                    $dayssince,
                    [$identity],
                    $detectedat,
                    ['dayssinceaccess' => $dayssince]
                )
            );

            return;
        }

        if ($dayssince >= 30) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.inactive_30d',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::NEGATIVE,
                    -40,
                    $dayssince,
                    [$identity],
                    $detectedat,
                    ['dayssinceaccess' => $dayssince]
                )
            );

            return;
        }

        if ($dayssince >= 14) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.inactive_14d',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::NEGATIVE,
                    -25,
                    $dayssince,
                    [$identity],
                    $detectedat,
                    ['dayssinceaccess' => $dayssince]
                )
            );

            return;
        }

        if ($dayssince >= 7) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.inactive_7d',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::NEGATIVE,
                    -12,
                    $dayssince,
                    [$identity],
                    $detectedat,
                    ['dayssinceaccess' => $dayssince]
                )
            );
        }
    }

    private function add_regularity_signal(
        SuccessSignalCollection $signals,
        int $userid,
        int $activeDays7d,
        int $activeDays30d,
        int $detectedat
    ): void {
        $metricidentities = [
            SuccessMetricSource::MOODLE_LOGS .
            ':activity.active_days_7d',
            SuccessMetricSource::MOODLE_LOGS .
            ':activity.active_days_30d',
        ];

        if (
            $activeDays7d >= 4 ||
            $activeDays30d >= 12
        ) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.strong_regularity',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::POSITIVE,
                    18,
                    $activeDays30d,
                    $metricidentities,
                    $detectedat,
                    [
                        'activedays7d' => $activeDays7d,
                        'activedays30d' => $activeDays30d,
                    ]
                )
            );

            return;
        }

        if (
            $activeDays7d >= 2 ||
            $activeDays30d >= 6
        ) {
            $signals->add(
                new SuccessSignal(
                    $userid,
                    'activity.moderate_regularity',
                    SuccessSignalCategory::ENGAGEMENT,
                    SuccessSignalPolarity::POSITIVE,
                    8,
                    $activeDays30d,
                    $metricidentities,
                    $detectedat,
                    [
                        'activedays7d' => $activeDays7d,
                        'activedays30d' => $activeDays30d,
                    ]
                )
            );
        }
    }

    private function integer_value(
        ?SuccessMetric $metric
    ): int {
        return $metric !== null
            ? (int)$metric->value
            : 0;
    }
}