<?php

namespace local_subscriptions\crm\success\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable-style collection of normalized Customer Success metrics.
 */
final class SuccessMetricCollection implements \Countable, \IteratorAggregate {

    /**
     * @var array<string, SuccessMetric>
     */
    private array $metrics = [];

    /**
     * @param SuccessMetric[] $metrics
     */
    public function __construct(array $metrics = []) {
        foreach ($metrics as $metric) {
            $this->add($metric);
        }
    }

    public function add(SuccessMetric $metric): void {
        $identity = $metric->identity();

        if (isset($this->metrics[$identity])) {
            throw new \InvalidArgumentException(
                'Duplicate Customer Success metric identity: ' . $identity
            );
        }

        if ($this->metrics !== []) {
            $existing = reset($this->metrics);

            if ($existing instanceof SuccessMetric && $existing->userid !== $metric->userid) {
                throw new \InvalidArgumentException(
                    'A Customer Success metric collection cannot contain multiple users.'
                );
            }
        }

        $this->metrics[$identity] = $metric;
    }

    public function has(string $source, string $key): bool {
        return isset($this->metrics[$source . ':' . $key]);
    }

    public function get(string $source, string $key): ?SuccessMetric {
        return $this->metrics[$source . ':' . $key] ?? null;
    }

    /**
     * @return SuccessMetric[]
     */
    public function all(): array {
        return array_values($this->metrics);
    }

    /**
     * @return SuccessMetric[]
     */
    public function by_source(string $source): array {
        return array_values(array_filter(
            $this->metrics,
            static fn(SuccessMetric $metric): bool => $metric->source === $source
        ));
    }

    public function userid(): ?int {
        $metric = reset($this->metrics);

        return $metric instanceof SuccessMetric ? $metric->userid : null;
    }

    public function count(): int {
        return count($this->metrics);
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return \stdClass[]
     */
    public function to_objects(): array {
        return array_map(
            static fn(SuccessMetric $metric): \stdClass => $metric->to_object(),
            $this->all()
        );
    }
}