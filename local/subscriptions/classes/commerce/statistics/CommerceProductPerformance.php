<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Product-level statistics, strictly isolated by currency. */
final class CommerceProductPerformance {
    /** @param array<string,array<string,int|float>> $bycurrency */
    public function __construct(
        private readonly string $reference,
        private readonly array $bycurrency,
        private readonly array $seriesbycurrency
    ) {
    }
    public function reference(): string { return $this->reference; }
    /** @return array<string,array<string,int|float>> */
    public function by_currency(): array { return $this->bycurrency; }
    /** @return array<string,CommerceStatisticsSeries> */
    public function series_by_currency(): array { return $this->seriesbycurrency; }
    public function is_empty(): bool { return $this->bycurrency === []; }
}
