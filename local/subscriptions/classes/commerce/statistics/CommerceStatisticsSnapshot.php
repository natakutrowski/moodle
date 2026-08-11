<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Complete statistics result for one period and its previous comparison period. */
final class CommerceStatisticsSnapshot {
    /** @var CommerceStatisticsMetric[] */
    private array $metrics = [];

    public function __construct(
        private readonly CommerceStatisticsPeriod $period,
        private readonly CommerceStatisticsPeriod $previousperiod
    ) {
    }

    public function add(CommerceStatisticsMetric $metric): void {
        $identity = $metric->key() . ':' . ($metric->currency() ?? '*');
        if (isset($this->metrics[$identity])) {
            throw new \coding_exception('Duplicate Commerce statistics metric: ' . $identity);
        }
        $this->metrics[$identity] = $metric;
    }

    /** @return CommerceStatisticsMetric[] */
    public function metrics(): array {
        return array_values($this->metrics);
    }

    public function get(string $key, ?string $currency = null): ?CommerceStatisticsMetric {
        return $this->metrics[$key . ':' . ($currency ?? '*')] ?? null;
    }

    public function period(): CommerceStatisticsPeriod { return $this->period; }
    public function previous_period(): CommerceStatisticsPeriod { return $this->previousperiod; }

    public function export(): array {
        return [
            'period' => ['start' => $this->period->start(), 'end' => $this->period->end()],
            'previous_period' => [
                'start' => $this->previousperiod->start(),
                'end' => $this->previousperiod->end(),
            ],
            'metrics' => array_map(static fn(CommerceStatisticsMetric $metric): array => $metric->export(), $this->metrics()),
        ];
    }
}
