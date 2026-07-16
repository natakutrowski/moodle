<?php

namespace local_subscriptions\crm\success\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\signals\SuccessSignal;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Aggregates normalized signals into explainable health scores.
 */
final class SuccessScoreEngine {

    public function __construct(
        private readonly SuccessScoreProfile $profile
    ) {
    }

    public function calculate(
        int $userid,
        SuccessSignalCollection $signals,
        ?int $calculatedat = null
    ): CustomerSuccessScore {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success score userid must be greater than zero.'
            );
        }

        if (
            $signals->userid() !== null &&
            $signals->userid() !== $userid
        ) {
            throw new \InvalidArgumentException(
                'Customer Success signals belong to another user.'
            );
        }

        $calculatedat = $calculatedat ?? time();

        if ($calculatedat <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success calculation timestamp must be greater than zero.'
            );
        }

        $dimensions = [];

        foreach (SuccessSignalCategory::all() as $category) {
            $categorysignals =
                $signals->by_category($category);

            $dimensions[$category] =
                $this->calculate_dimension(
                    $category,
                    $categorysignals
                );
        }

        $global = $this->calculate_global(
            $dimensions
        );

        return new CustomerSuccessScore(
            $userid,
            $global,
            $global !== null
                ? SuccessHealthLevel::from_score($global)
                : null,
            $dimensions,
            $calculatedat
        );
    }

    /**
     * @param SuccessSignal[] $signals
     */
    private function calculate_dimension(
        string $category,
        array $signals
    ): SuccessDimensionScore {
        $scoringsignals = array_values(
            array_filter(
                $signals,
                static fn(
                    SuccessSignal $signal
                ): bool => $signal->weight !== 0
            )
        );

        if ($scoringsignals === []) {
            return new SuccessDimensionScore(
                $category,
                null,
                null,
                0,
                $signals
            );
        }

        $contribution = array_reduce(
            $scoringsignals,
            static fn(
                int $total,
                SuccessSignal $signal
            ): int => $total + $signal->weight,
            0
        );

        $score = max(
            0,
            min(
                100,
                $this->profile->baseline($category) +
                    $contribution
            )
        );

        return new SuccessDimensionScore(
            $category,
            $score,
            SuccessHealthLevel::from_score($score),
            $contribution,
            $signals
        );
    }

    /**
     * @param array<string,SuccessDimensionScore> $dimensions
     */
    private function calculate_global(
        array $dimensions
    ): ?int {

        $availabledimensions = array_filter(
            $dimensions,
            static fn(
                SuccessDimensionScore $dimension
            ): bool => $dimension->is_available()
        );

        if (count($availabledimensions) < 2) {
            return null;
        }

        $weightedtotal = 0.0;
        $availableweight = 0.0;

        foreach ($dimensions as $category => $dimension) {
            if (!$dimension->is_available()) {
                continue;
            }

            $weight =
                $this->profile->global_weight($category);

            if ($weight <= 0) {
                continue;
            }

            $weightedtotal +=
                (float)$dimension->score * $weight;

            $availableweight += $weight;
        }

        if ($availableweight <= 0) {
            return null;
        }

        return (int)round(
            $weightedtotal / $availableweight
        );
    }
}