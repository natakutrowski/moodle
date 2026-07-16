<?php

namespace local_subscriptions\crm\success\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessSignalCategory;

/**
 * Complete explainable Customer Success score.
 */
final class CustomerSuccessScore {

    /**
     * @param int $userid Moodle user ID.
     * @param int|null $global Global health score.
     * @param string|null $level Global health level.
     * @param array<string,SuccessDimensionScore> $dimensions Scores by category.
     * @param int $calculatedat Calculation timestamp.
     */
    public function __construct(
        public readonly int $userid,
        public readonly ?int $global,
        public readonly ?string $level,
        private readonly array $dimensions,
        public readonly int $calculatedat
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success score userid must be greater than zero.'
            );
        }

        if (
            $this->global !== null &&
            ($this->global < 0 || $this->global > 100)
        ) {
            throw new \InvalidArgumentException(
                'Global Customer Success score must be between zero and 100.'
            );
        }

        if (
            $this->global === null &&
            $this->level !== null
        ) {
            throw new \InvalidArgumentException(
                'A missing global score cannot have a health level.'
            );
        }

        if (
            $this->global !== null &&
            (
                $this->level === null ||
                !SuccessHealthLevel::is_valid($this->level)
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid global Customer Success health level.'
            );
        }

        if ($this->calculatedat <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success calculation timestamp must be greater than zero.'
            );
        }

        foreach ($this->dimensions as $category => $dimension) {
            if (
                !is_string($category) ||
                !SuccessSignalCategory::is_valid($category) ||
                !$dimension instanceof SuccessDimensionScore ||
                $dimension->category !== $category
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success dimension map.'
                );
            }
        }
    }

    public function dimension(
        string $category
    ): ?SuccessDimensionScore {
        return $this->dimensions[$category] ?? null;
    }

    /**
     * @return array<string,SuccessDimensionScore>
     */
    public function dimensions(): array {
        return $this->dimensions;
    }

    /**
     * Risk exposure follows the historical CRM convention:
     * 100 means maximum exposure and zero means no detected exposure.
     */
    public function risk_exposure(): ?int {
        $dimension = $this->dimension(
            SuccessSignalCategory::RISK
        );

        if (
            $dimension === null ||
            $dimension->score === null
        ) {
            return null;
        }

        return 100 - $dimension->score;
    }

    public function to_object(): \stdClass {
        $dimensions = [];

        foreach ($this->dimensions as $category => $dimension) {
            $dimensions[$category] = $dimension->to_object();
        }

        return (object)[
            'userid' => $this->userid,
            'global' => $this->global,
            'level' => $this->level,
            'riskexposure' => $this->risk_exposure(),
            'dimensions' => $dimensions,
            'calculatedat' => $this->calculatedat,
            'availabledimensioncount' =>
                $this->available_dimension_count(),
        ];
    }

    public function available_dimension_count(): int {
        return count(
            array_filter(
                $this->dimensions,
                static fn(
                    SuccessDimensionScore $dimension
                ): bool => $dimension->is_available()
            )
        );
    }

}