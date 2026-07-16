<?php

namespace local_subscriptions\crm\success\collection;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\domain\SuccessMetricSource;

/**
 * Immutable normalized metric collected from Moodle, CRM or a plugin.
 *
 * A metric is a factual observation. It must not contain scoring logic.
 */
final class SuccessMetric {

    /**
     * @param int $userid Moodle user ID.
     * @param string $key Stable technical metric key.
     * @param int|float|string|bool|null $value Normalized scalar value.
     * @param string $source Metric source identifier.
     * @param int $measuredat Timestamp at which the value was observed.
     * @param array $metadata Non-sensitive contextual metadata.
     */
    public function __construct(
        public readonly int $userid,
        public readonly string $key,
        public readonly int|float|string|bool|null $value,
        public readonly string $source,
        public readonly int $measuredat,
        public readonly array $metadata = []
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException('Success metric userid must be greater than zero.');
        }

        if (!$this->is_valid_key($this->key)) {
            throw new \InvalidArgumentException('Invalid Customer Success metric key.');
        }

        if (!SuccessMetricSource::is_valid($this->source)) {
            throw new \InvalidArgumentException('Invalid Customer Success metric source.');
        }

        if ($this->measuredat <= 0) {
            throw new \InvalidArgumentException('Success metric timestamp must be greater than zero.');
        }

        $this->validate_metadata($this->metadata);
    }

    /**
     * Builds the unique identity of this metric inside a collection.
     */
    public function identity(): string {
        return $this->source . ':' . $this->key;
    }

    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'key' => $this->key,
            'value' => $this->value,
            'source' => $this->source,
            'measuredat' => $this->measuredat,
            'metadata' => $this->metadata,
        ];
    }

    private function is_valid_key(string $key): bool {
        return preg_match('/^[a-z][a-z0-9_.]{1,99}$/', $key) === 1;
    }

    private function validate_metadata(array $metadata): void {
        foreach ($metadata as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Success metric metadata keys must be strings.');
            }

            if (
                $value !== null &&
                !is_scalar($value) &&
                !is_array($value)
            ) {
                throw new \InvalidArgumentException(
                    'Success metric metadata values must be scalar, null or arrays.'
                );
            }
        }
    }
}