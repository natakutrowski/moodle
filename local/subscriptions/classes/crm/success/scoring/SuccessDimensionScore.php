<?php

namespace local_subscriptions\crm\success\scoring;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\signals\SuccessSignal;

/**
 * Explainable score for one Customer Success dimension.
 */
final class SuccessDimensionScore {

    /**
     * @param string $category Customer Success category.
     * @param int|null $score Null when no usable signal exists.
     * @param string|null $level Normalized health level.
     * @param int $signedcontribution Sum of signal weights.
     * @param SuccessSignal[] $signals Signals used by the dimension.
     */
    public function __construct(
        public readonly string $category,
        public readonly ?int $score,
        public readonly ?string $level,
        public readonly int $signedcontribution,
        public readonly array $signals
    ) {
        if (!SuccessSignalCategory::is_valid($this->category)) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success score category.'
            );
        }

        if (
            $this->score !== null &&
            ($this->score < 0 || $this->score > 100)
        ) {
            throw new \InvalidArgumentException(
                'Customer Success dimension score must be between zero and 100.'
            );
        }

        if (
            $this->score === null &&
            $this->level !== null
        ) {
            throw new \InvalidArgumentException(
                'A missing dimension score cannot have a health level.'
            );
        }

        if (
            $this->score !== null &&
            (
                $this->level === null ||
                !SuccessHealthLevel::is_valid($this->level)
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success dimension health level.'
            );
        }

        foreach ($this->signals as $signal) {
            if (!$signal instanceof SuccessSignal) {
                throw new \InvalidArgumentException(
                    'Invalid signal in Customer Success dimension.'
                );
            }

            if ($signal->category !== $this->category) {
                throw new \InvalidArgumentException(
                    'Signal category does not match its score dimension.'
                );
            }
        }
    }

    public function is_available(): bool {
        return $this->score !== null;
    }

    public function signal_count(): int {
        return count($this->signals);
    }

    public function to_object(): \stdClass {
        return (object)[
            'category' => $this->category,
            'score' => $this->score,
            'level' => $this->level,
            'signedcontribution' => $this->signedcontribution,
            'signalcount' => $this->signal_count(),
            'signals' => array_map(
                static fn(SuccessSignal $signal): \stdClass =>
                    $signal->to_object(),
                $this->signals
            ),
        ];
    }
}