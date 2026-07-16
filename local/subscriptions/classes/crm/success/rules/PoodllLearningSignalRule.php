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
 * Converts aggregated Poodll metrics into learning signals.
 */
final class PoodllLearningSignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'poodll_learning_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return count(
            $metrics->by_source(
                SuccessMetricSource::POODLL
            )
        ) > 0;
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

        $this->evaluate_minilesson(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        $this->evaluate_readaloud(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        $this->evaluate_solo(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        $this->evaluate_wordcards(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        return $signals;
    }

    private function evaluate_minilesson(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $attempts30d = $this->integer_value(
            $metrics,
            'minilesson.attempts_30d'
        );

        $averagescore = $this->nullable_float(
            $metrics,
            'minilesson.average_score'
        );

        if ($attempts30d >= 4) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.minilesson_regular_practice',
                    8,
                    $attempts30d,
                    ['minilesson.attempts_30d'],
                    $detectedat
                )
            );
        }

        if (
            $averagescore !== null &&
            $averagescore >= 80
        ) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.minilesson_high_performance',
                    12,
                    $averagescore,
                    ['minilesson.average_score'],
                    $detectedat
                )
            );
        } else if (
            $averagescore !== null &&
            $averagescore < 50 &&
            $attempts30d >= 3
        ) {
            $signals->add(
                $this->negative(
                    $userid,
                    'learning.minilesson_repeated_difficulty',
                    -10,
                    $averagescore,
                    [
                        'minilesson.average_score',
                        'minilesson.attempts_30d',
                    ],
                    $detectedat
                )
            );
        }
    }

    private function evaluate_readaloud(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $attempts30d = $this->integer_value(
            $metrics,
            'readaloud.attempts_30d'
        );

        $accuracy = $this->nullable_float(
            $metrics,
            'readaloud.average_accuracy'
        );

        if ($attempts30d >= 4) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.readaloud_regular_practice',
                    8,
                    $attempts30d,
                    ['readaloud.attempts_30d'],
                    $detectedat
                )
            );
        }

        if ($accuracy !== null && $accuracy >= 85) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.readaloud_high_accuracy',
                    15,
                    $accuracy,
                    ['readaloud.average_accuracy'],
                    $detectedat
                )
            );
        } else if (
            $accuracy !== null &&
            $accuracy < 60 &&
            $attempts30d >= 3
        ) {
            $signals->add(
                $this->negative(
                    $userid,
                    'learning.readaloud_persistent_difficulty',
                    -12,
                    $accuracy,
                    [
                        'readaloud.average_accuracy',
                        'readaloud.attempts_30d',
                    ],
                    $detectedat
                )
            );
        }
    }

    private function evaluate_solo(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $attempts30d = $this->integer_value(
            $metrics,
            'solo.attempts_30d'
        );

        $accuracy = $this->nullable_float(
            $metrics,
            'solo.average_accuracy'
        );

        $words = $this->integer_value(
            $metrics,
            'solo.total_words'
        );

        if ($attempts30d >= 3) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.solo_regular_speaking',
                    8,
                    $attempts30d,
                    ['solo.attempts_30d'],
                    $detectedat
                )
            );
        }

        if ($accuracy !== null && $accuracy >= 80) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.solo_strong_accuracy',
                    12,
                    $accuracy,
                    ['solo.average_accuracy'],
                    $detectedat
                )
            );
        }

        if ($words >= 500) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.solo_meaningful_oral_output',
                    7,
                    $words,
                    ['solo.total_words'],
                    $detectedat
                )
            );
        }
    }

    private function evaluate_wordcards(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $terms = $this->integer_value(
            $metrics,
            'wordcards.distinct_term_count'
        );

        $interactions = $this->integer_value(
            $metrics,
            'wordcards.interaction_count'
        );

        $successrate = $this->nullable_float(
            $metrics,
            'wordcards.success_rate_percentage'
        );

        $seen30d = $this->integer_value(
            $metrics,
            'wordcards.seen_30d'
        );

        if ($seen30d >= 20) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.wordcards_regular_revision',
                    9,
                    $seen30d,
                    ['wordcards.seen_30d'],
                    $detectedat
                )
            );
        }

        if ($terms >= 30) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.wordcards_vocabulary_growth',
                    8,
                    $terms,
                    ['wordcards.distinct_term_count'],
                    $detectedat
                )
            );
        }

        if (
            $successrate !== null &&
            $successrate >= 80 &&
            $interactions >= 20
        ) {
            $signals->add(
                $this->signal(
                    $userid,
                    'learning.wordcards_high_success_rate',
                    12,
                    $successrate,
                    [
                        'wordcards.success_rate_percentage',
                        'wordcards.interaction_count',
                    ],
                    $detectedat
                )
            );
        } else if (
            $successrate !== null &&
            $successrate < 50 &&
            $interactions >= 20
        ) {
            $signals->add(
                $this->negative(
                    $userid,
                    'learning.wordcards_repeated_difficulty',
                    -10,
                    $successrate,
                    [
                        'wordcards.success_rate_percentage',
                        'wordcards.interaction_count',
                    ],
                    $detectedat
                )
            );
        }
    }

    private function signal(
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
            SuccessSignalCategory::LEARNING,
            SuccessSignalPolarity::POSITIVE,
            $weight,
            $value,
            array_map(
                fn(string $key): string =>
                    $this->identity($key),
                $metrickeys
            ),
            $detectedat
        );
    }

    private function negative(
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
            SuccessSignalCategory::LEARNING,
            SuccessSignalPolarity::NEGATIVE,
            $weight,
            $value,
            array_map(
                fn(string $key): string =>
                    $this->identity($key),
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
            SuccessMetricSource::POODLL,
            'learning.poodll.' . $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function nullable_float(
        SuccessMetricCollection $metrics,
        string $key
    ): ?float {
        $metric = $metrics->get(
            SuccessMetricSource::POODLL,
            'learning.poodll.' . $key
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
            SuccessMetricSource::POODLL .
            ':learning.poodll.' .
            $key;
    }
}