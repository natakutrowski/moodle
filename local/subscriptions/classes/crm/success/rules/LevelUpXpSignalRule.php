<?php

namespace local_subscriptions\crm\success\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessSignalRuleInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\domain\SuccessSignalPolarity;
use local_subscriptions\crm\success\signals\SuccessSignal;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Converts Level Up XP metrics into gamification signals.
 */
final class LevelUpXpSignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'levelup_xp_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return $metrics->has(
            SuccessMetricSource::LEVELUP_XP,
            'gamification.levelup.enabled_course_count'
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

        $enabledcourses = $this->integer_value(
            $metrics,
            'enabled_course_count'
        );

        if ($enabledcourses <= 0) {
            return $signals;
        }

        $totalxp = $this->integer_value(
            $metrics,
            'total_xp'
        );

        $xp7d = $this->integer_value(
            $metrics,
            'xp_7d'
        );

        $xp30d = $this->integer_value(
            $metrics,
            'xp_30d'
        );

        $activedays30d = $this->integer_value(
            $metrics,
            'active_days_30d'
        );

        $courseswithxp = $this->integer_value(
            $metrics,
            'course_count_with_xp'
        );

        $lastrewardat = $this->integer_value(
            $metrics,
            'last_reward_at'
        );

        if ($xp7d >= 100) {
            $signals->add(
                $this->positive(
                    $userid,
                    'gamification.strong_recent_xp_growth',
                    18,
                    $xp7d,
                    ['xp_7d'],
                    $detectedat
                )
            );
        } else if ($xp7d > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'gamification.recent_xp_growth',
                    9,
                    $xp7d,
                    ['xp_7d'],
                    $detectedat
                )
            );
        }

        if ($activedays30d >= 10) {
            $signals->add(
                $this->positive(
                    $userid,
                    'gamification.regular_xp_activity',
                    15,
                    $activedays30d,
                    ['active_days_30d'],
                    $detectedat
                )
            );
        } else if ($activedays30d >= 4) {
            $signals->add(
                $this->positive(
                    $userid,
                    'gamification.moderate_xp_activity',
                    7,
                    $activedays30d,
                    ['active_days_30d'],
                    $detectedat
                )
            );
        }

        if ($courseswithxp >= 2) {
            $signals->add(
                $this->positive(
                    $userid,
                    'gamification.multi_course_xp',
                    6,
                    $courseswithxp,
                    ['course_count_with_xp'],
                    $detectedat
                )
            );
        }

        if (
            $totalxp > 0 &&
            $xp30d === 0 &&
            $lastrewardat > 0
        ) {
            $dayssince = max(
                0,
                (int)floor(
                    ($detectedat - $lastrewardat) / DAYSECS
                )
            );

            if ($dayssince >= 30) {
                $signals->add(
                    new SuccessSignal(
                        $userid,
                        'gamification.no_recent_xp',
                        SuccessSignalCategory::GAMIFICATION,
                        SuccessSignalPolarity::NEGATIVE,
                        -15,
                        $dayssince,
                        [
                            $this->identity('last_reward_at'),
                            $this->identity('xp_30d'),
                        ],
                        $detectedat,
                        [
                            'dayssincereward' => $dayssince,
                        ]
                    )
                );
            }
        }

        return $signals;
    }

    private function positive(
        int $userid,
        string $key,
        int $weight,
        int|float $value,
        array $metrickeys,
        int $detectedat
    ): SuccessSignal {
        return new SuccessSignal(
            $userid,
            $key,
            SuccessSignalCategory::GAMIFICATION,
            SuccessSignalPolarity::POSITIVE,
            $weight,
            $value,
            array_map(
                fn(string $metrickey): string =>
                    $this->identity($metrickey),
                $metrickeys
            ),
            $detectedat
        );
    }

    private function integer_value(
        SuccessMetricCollection $metrics,
        string $key
    ): int {
        $metric = $metrics->get(
            SuccessMetricSource::LEVELUP_XP,
            'gamification.levelup.' . $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function identity(
        string $key
    ): string {
        return
            SuccessMetricSource::LEVELUP_XP .
            ':gamification.levelup.' .
            $key;
    }
}