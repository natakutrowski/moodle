<?php

namespace local_subscriptions\crm\success\signals;

defined('MOODLE_INTERNAL') || die();

/**
 * Collection of explainable Customer Success signals for one user.
 */
final class SuccessSignalCollection implements \Countable, \IteratorAggregate {

    /**
     * @var array<string, SuccessSignal>
     */
    private array $signals = [];

    /**
     * @param SuccessSignal[] $signals
     */
    public function __construct(array $signals = []) {
        foreach ($signals as $signal) {
            $this->add($signal);
        }
    }

    public function add(SuccessSignal $signal): void {
        $identity = $signal->identity();

        if (isset($this->signals[$identity])) {
            throw new \InvalidArgumentException(
                'Duplicate Customer Success signal identity: ' . $identity
            );
        }

        if ($this->signals !== []) {
            $existing = reset($this->signals);

            if ($existing instanceof SuccessSignal && $existing->userid !== $signal->userid) {
                throw new \InvalidArgumentException(
                    'A Customer Success signal collection cannot contain multiple users.'
                );
            }
        }

        $this->signals[$identity] = $signal;
    }

    public function has(string $category, string $key): bool {
        return isset($this->signals[$category . ':' . $key]);
    }

    public function get(string $category, string $key): ?SuccessSignal {
        return $this->signals[$category . ':' . $key] ?? null;
    }

    /**
     * @return SuccessSignal[]
     */
    public function all(): array {
        return array_values($this->signals);
    }

    /**
     * @return SuccessSignal[]
     */
    public function by_category(string $category): array {
        return array_values(array_filter(
            $this->signals,
            static fn(SuccessSignal $signal): bool => $signal->category === $category
        ));
    }

    /**
     * Returns the signed sum of the signal weights for one category.
     */
    public function weight_for_category(string $category): int {
        return array_reduce(
            $this->by_category($category),
            static fn(int $total, SuccessSignal $signal): int => $total + $signal->weight,
            0
        );
    }

    public function userid(): ?int {
        $signal = reset($this->signals);

        return $signal instanceof SuccessSignal ? $signal->userid : null;
    }

    public function count(): int {
        return count($this->signals);
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return \stdClass[]
     */
    public function to_objects(): array {
        return array_map(
            static fn(SuccessSignal $signal): \stdClass => $signal->to_object(),
            $this->all()
        );
    }
}