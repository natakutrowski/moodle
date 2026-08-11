<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Named metric, optionally scoped to one currency. */
final class CommerceStatisticsMetric {
    public function __construct(
        private readonly string $key,
        private readonly CommerceStatisticsComparison $comparison,
        private readonly ?string $currency = null
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new \coding_exception('Invalid Commerce statistics metric key.');
        }
    }

    public function key(): string { return $this->key; }
    public function comparison(): CommerceStatisticsComparison { return $this->comparison; }
    public function currency(): ?string { return $this->currency; }

    public function export(): array {
        return [
            'key' => $this->key,
            'currency' => $this->currency,
            'comparison' => $this->comparison->export(),
        ];
    }
}
