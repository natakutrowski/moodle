<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\dto\CustomerSuccessPriorityScore;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Calculates deterministic action priority.
 *
 * Formula:
 * - impact: 35%
 * - urgency: 30%
 * - customer/commercial value: 25%
 * - low effort bonus: 10%
 */
final class CustomerSuccessPrioritizer {

    public function score(
        CustomerSuccessRecommendationInput $recommendation
    ): CustomerSuccessPriorityScore {
        $effortbonus =
            100 - $recommendation->effortscore;

        $score =
            ($recommendation->impactscore * 0.35) +
            ($recommendation->urgencyscore * 0.30) +
            ($recommendation->valuescore * 0.25) +
            ($effortbonus * 0.10);

        $score += $this->priority_bonus(
            $recommendation->priority
        );

        $score = round(
            max(
                0,
                min(100, $score)
            ),
            2
        );

        return new CustomerSuccessPriorityScore(
            recommendationid:
                $recommendation->recommendationid,
            score: $score,
            impactscore:
                $recommendation->impactscore,
            urgencyscore:
                $recommendation->urgencyscore,
            valuescore:
                $recommendation->valuescore,
            effortscore:
                $recommendation->effortscore,
            priority:
                $this->score_to_priority(
                    $score
                )
        );
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     * @return array<int,CustomerSuccessPriorityScore>
     */
    public function score_all(
        array $recommendations
    ): array {
        $result = [];

        foreach ($recommendations as $recommendation) {
            if (
                !$recommendation instanceof
                CustomerSuccessRecommendationInput
            ) {
                throw new \InvalidArgumentException(
                    'Prioritizer requires CustomerSuccessRecommendationInput objects.'
                );
            }

            $score = $this->score(
                $recommendation
            );

            $result[
                $recommendation->recommendationid
            ] = $score;
        }

        return $result;
    }

    /**
     * @param CustomerSuccessRecommendationInput[] $recommendations
     * @return CustomerSuccessRecommendationInput[]
     */
    public function sort(
        array $recommendations
    ): array {
        $scores = $this->score_all(
            $recommendations
        );

        usort(
            $recommendations,
            static function (
                CustomerSuccessRecommendationInput $left,
                CustomerSuccessRecommendationInput $right
            ) use ($scores): int {
                $leftscore =
                    $scores[$left->recommendationid]
                        ->score;

                $rightscore =
                    $scores[$right->recommendationid]
                        ->score;

                if ($leftscore === $rightscore) {
                    return $left->recommendationid <=>
                        $right->recommendationid;
                }

                return $rightscore <=>
                    $leftscore;
            }
        );

        return array_values(
            $recommendations
        );
    }

    private function priority_bonus(
        string $priority
    ): int {
        return match (
            \core_text::strtolower(
                trim($priority)
            )
        ) {
            'critical' => 15,
            'urgent' => 10,
            'high' => 5,
            'low' => -5,
            default => 0,
        };
    }

    public function score_to_priority(
        float $score
    ): string {
        if ($score >= 90) {
            return 'critical';
        }

        if ($score >= 75) {
            return 'urgent';
        }

        if ($score >= 60) {
            return 'high';
        }

        if ($score >= 35) {
            return 'normal';
        }

        return 'low';
    }
}