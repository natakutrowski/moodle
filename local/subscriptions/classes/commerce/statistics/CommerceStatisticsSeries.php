<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Immutable chart-ready time series. */
final class CommerceStatisticsSeries {
    /** @param array<int,array{timestamp:int,label:string,value:int|float}> $points */
    public function __construct(
        private readonly string $key,
        private readonly ?string $currency,
        private readonly string $granularity,
        private readonly array $points
    ) {
    }

    public function key(): string { return $this->key; }
    public function currency(): ?string { return $this->currency; }
    public function granularity(): string { return $this->granularity; }
    /** @return array<int,array{timestamp:int,label:string,value:int|float}> */
    public function points(): array { return $this->points; }
    /** @return string[] */
    public function labels(): array { return array_column($this->points, 'label'); }
    /** @return array<int,int|float> */
    public function values(): array { return array_column($this->points, 'value'); }
}
