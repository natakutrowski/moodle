<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Comparison between a current metric and the equivalent previous period. */
final class CommerceStatisticsComparison {
    public const TREND_UP = 'up';
    public const TREND_DOWN = 'down';
    public const TREND_FLAT = 'flat';
    public const TREND_NOT_AVAILABLE = 'not_available';

    private function __construct(
        private readonly int|float $current,
        private readonly int|float|null $previous,
        private readonly int|float|null $delta,
        private readonly ?float $deltapercent,
        private readonly string $trend
    ) {
    }

    public static function compare(int|float $current, int|float|null $previous): self {
        if ($previous === null) {
            return new self($current, null, null, null, self::TREND_NOT_AVAILABLE);
        }
        $delta = $current - $previous;
        $trend = $delta > 0 ? self::TREND_UP : ($delta < 0 ? self::TREND_DOWN : self::TREND_FLAT);
        $percent = $previous == 0 ? null : ($delta / abs($previous)) * 100;
        return new self($current, $previous, $delta, $percent, $trend);
    }

    public function current(): int|float { return $this->current; }
    public function previous(): int|float|null { return $this->previous; }
    public function delta(): int|float|null { return $this->delta; }
    public function delta_percent(): ?float { return $this->deltapercent; }
    public function trend(): string { return $this->trend; }

    public function export(): array {
        return [
            'current' => $this->current,
            'previous' => $this->previous,
            'delta' => $this->delta,
            'delta_percent' => $this->deltapercent,
            'trend' => $this->trend,
        ];
    }
}
