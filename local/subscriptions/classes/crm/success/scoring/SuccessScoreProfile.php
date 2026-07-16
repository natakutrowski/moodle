<?php

namespace local_subscriptions\crm\success\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessSignalCategory;

/**
 * Defines normalized dimension baselines and global weights.
 */
final class SuccessScoreProfile {

    /**
     * @param array<string,int> $baselines Score baseline by category.
     * @param array<string,float|int> $globalweights Global weight by category.
     */
    public function __construct(
        private readonly array $baselines,
        private readonly array $globalweights
    ) {
        foreach (SuccessSignalCategory::all() as $category) {
            if (!array_key_exists($category, $this->baselines)) {
                throw new \InvalidArgumentException(
                    'Missing Customer Success baseline for ' . $category . '.'
                );
            }

            $baseline = $this->baselines[$category];

            if (
                !is_int($baseline) ||
                $baseline < 0 ||
                $baseline > 100
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success baseline for ' . $category . '.'
                );
            }

            if (!array_key_exists($category, $this->globalweights)) {
                throw new \InvalidArgumentException(
                    'Missing Customer Success global weight for ' . $category . '.'
                );
            }

            $weight = $this->globalweights[$category];

            if (
                !is_int($weight) &&
                !is_float($weight)
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Customer Success weight for ' . $category . '.'
                );
            }

            if ($weight < 0) {
                throw new \InvalidArgumentException(
                    'Customer Success weights cannot be negative.'
                );
            }
        }
    }

    public static function campusfr_default(): self {
        return new self(
            [
                SuccessSignalCategory::COMMERCIAL => 50,
                SuccessSignalCategory::ENGAGEMENT => 50,
                SuccessSignalCategory::LEARNING => 50,
                SuccessSignalCategory::SUPPORT => 50,
                SuccessSignalCategory::LOYALTY => 50,
                SuccessSignalCategory::RISK => 50,
                SuccessSignalCategory::GAMIFICATION => 50,
            ],
            [
                SuccessSignalCategory::COMMERCIAL => 15,
                SuccessSignalCategory::ENGAGEMENT => 25,
                SuccessSignalCategory::LEARNING => 25,
                SuccessSignalCategory::SUPPORT => 10,
                SuccessSignalCategory::LOYALTY => 10,
                SuccessSignalCategory::RISK => 10,
                SuccessSignalCategory::GAMIFICATION => 5,
            ]
        );
    }

    public function baseline(
        string $category
    ): int {
        if (!SuccessSignalCategory::is_valid($category)) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success score category.'
            );
        }

        return $this->baselines[$category];
    }

    public function global_weight(
        string $category
    ): float {
        if (!SuccessSignalCategory::is_valid($category)) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success score category.'
            );
        }

        return (float)$this->globalweights[$category];
    }

    /**
     * @return array<string,int>
     */
    public function baselines(): array {
        return $this->baselines;
    }

    /**
     * @return array<string,float|int>
     */
    public function global_weights(): array {
        return $this->globalweights;
    }
}