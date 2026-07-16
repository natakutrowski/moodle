<?php

namespace local_subscriptions\crm\success\signals;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\domain\SuccessSignalPolarity;

/**
 * Immutable explainable signal produced from one or more metrics.
 */
final class SuccessSignal {

    /**
     * @param int $userid Moodle user ID.
     * @param string $key Stable technical signal key.
     * @param string $category Customer Success score category.
     * @param string $polarity Positive, neutral or negative.
     * @param int $weight Signed weight, limited to -100..100.
     * @param int|float|string|bool|null $value Observed value behind the signal.
     * @param string[] $metricidentities Metrics that produced this signal.
     * @param int $detectedat Detection timestamp.
     * @param array $context Non-sensitive explainability context.
     */
    public function __construct(
        public readonly int $userid,
        public readonly string $key,
        public readonly string $category,
        public readonly string $polarity,
        public readonly int $weight,
        public readonly int|float|string|bool|null $value,
        public readonly array $metricidentities,
        public readonly int $detectedat,
        public readonly array $context = []
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException('Success signal userid must be greater than zero.');
        }

        if (!$this->is_valid_key($this->key)) {
            throw new \InvalidArgumentException('Invalid Customer Success signal key.');
        }

        if (!SuccessSignalCategory::is_valid($this->category)) {
            throw new \InvalidArgumentException('Invalid Customer Success signal category.');
        }

        if (!SuccessSignalPolarity::is_valid($this->polarity)) {
            throw new \InvalidArgumentException('Invalid Customer Success signal polarity.');
        }

        if ($this->weight < -100 || $this->weight > 100) {
            throw new \InvalidArgumentException('Success signal weight must be between -100 and 100.');
        }

        if (
            $this->polarity === SuccessSignalPolarity::POSITIVE &&
            $this->weight <= 0
        ) {
            throw new \InvalidArgumentException('A positive signal must have a positive weight.');
        }

        if (
            $this->polarity === SuccessSignalPolarity::NEGATIVE &&
            $this->weight >= 0
        ) {
            throw new \InvalidArgumentException('A negative signal must have a negative weight.');
        }

        if (
            $this->polarity === SuccessSignalPolarity::NEUTRAL &&
            $this->weight !== 0
        ) {
            throw new \InvalidArgumentException('A neutral signal must have a zero weight.');
        }

        if ($this->detectedat <= 0) {
            throw new \InvalidArgumentException('Success signal timestamp must be greater than zero.');
        }

        $this->validate_metric_identities($this->metricidentities);
        $this->validate_context($this->context);
    }

    public function identity(): string {
        return $this->category . ':' . $this->key;
    }

    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'key' => $this->key,
            'category' => $this->category,
            'polarity' => $this->polarity,
            'weight' => $this->weight,
            'value' => $this->value,
            'metricidentities' => $this->metricidentities,
            'detectedat' => $this->detectedat,
            'context' => $this->context,
        ];
    }

    private function is_valid_key(string $key): bool {
        return preg_match('/^[a-z][a-z0-9_.]{1,99}$/', $key) === 1;
    }

    private function validate_metric_identities(array $identities): void {
        foreach ($identities as $identity) {
            if (
                !is_string($identity) ||
                preg_match('/^[a-z][a-z0-9_]*:[a-z][a-z0-9_.]{1,99}$/', $identity) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Invalid metric identity attached to a Customer Success signal.'
                );
            }
        }
    }

    private function validate_context(array $context): void {
        foreach ($context as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Success signal context keys must be strings.');
            }

            if (
                $value !== null &&
                !is_scalar($value) &&
                !is_array($value)
            ) {
                throw new \InvalidArgumentException(
                    'Success signal context values must be scalar, null or arrays.'
                );
            }
        }
    }
}