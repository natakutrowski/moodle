<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable and explainable fact supporting a recommendation.
 *
 * Evidence contains structured technical data only. Human-readable labels
 * will later be resolved through Moodle language strings by presentation
 * services and renderers.
 */
final class RecommendationEvidence {

    /**
     * @param string $source RecommendationSource value.
     * @param string $key Stable technical evidence key.
     * @param int|float|string|bool|null $value Observed value.
     * @param int $weight Importance of this evidence, from 0 to 100.
     * @param int $detectedat Timestamp at which the evidence was observed.
     * @param array $context Non-sensitive explainability context.
     */
    public function __construct(
        public readonly string $source,
        public readonly string $key,
        public readonly int|float|string|bool|null $value,
        public readonly int $weight,
        public readonly int $detectedat,
        public readonly array $context = []
    ) {
        if (!RecommendationSource::is_valid($this->source)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation evidence source.'
            );
        }

        if (!$this->is_valid_key($this->key)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation evidence key.'
            );
        }

        if ($this->weight < 0 || $this->weight > 100) {
            throw new \InvalidArgumentException(
                'Recommendation evidence weight must be between 0 and 100.'
            );
        }

        if ($this->detectedat <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation evidence timestamp must be greater than zero.'
            );
        }

        $this->validate_context($this->context);
    }

    /**
     * Stable identity of the evidence.
     */
    public function identity(): string {
        return $this->source . ':' . $this->key;
    }

    /**
     * Serialize evidence for DTOs, APIs and renderers.
     */
    public function to_object(): \stdClass {
        return (object)[
            'source' => $this->source,
            'key' => $this->key,
            'identity' => $this->identity(),
            'value' => $this->value,
            'weight' => $this->weight,
            'detectedat' => $this->detectedat,
            'context' => $this->context,
        ];
    }

    /**
     * Validate a stable technical key.
     */
    private function is_valid_key(string $key): bool {
        return preg_match(
            '/^[a-z][a-z0-9_.]{1,99}$/',
            $key
        ) === 1;
    }

    /**
     * Ensure explainability context remains serializable and non-object based.
     */
    private function validate_context(array $context): void {
        foreach ($context as $key => $value) {
            if (
                !is_string($key) ||
                preg_match('/^[a-z][a-z0-9_]{0,49}$/', $key) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation evidence context keys must be stable technical strings.'
                );
            }

            if (!$this->is_valid_context_value($value)) {
                throw new \InvalidArgumentException(
                    'Recommendation evidence context contains an unsupported value.'
                );
            }
        }
    }

    /**
     * Validate context recursively.
     */
    private function is_valid_context_value(mixed $value): bool {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $nestedvalue) {
            if (!$this->is_valid_context_value($nestedvalue)) {
                return false;
            }
        }

        return true;
    }
}