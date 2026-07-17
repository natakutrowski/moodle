<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable result returned by a recommendation generator.
 */
final class RecommendationGenerationResult {

    public const STATUS_SUCCESS = 'success';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    /**
     * @var Recommendation[]
     */
    public readonly array $recommendations;

    /**
     * @param string $generatorkey Stable generator identifier.
     * @param string $status Generation status.
     * @param Recommendation[] $recommendations Generated recommendations.
     * @param string|null $reason Stable technical skip or failure reason.
     * @param array $metadata Non-sensitive diagnostic metadata.
     */
    private function __construct(
        public readonly string $generatorkey,
        public readonly string $status,
        array $recommendations = [],
        public readonly ?string $reason = null,
        public readonly array $metadata = []
    ) {
        if (!$this->is_valid_key($this->generatorkey)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation generator key.'
            );
        }

        if (!in_array($this->status, self::statuses(), true)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation generation status.'
            );
        }

        if (
            $this->status === self::STATUS_SUCCESS &&
            $this->reason !== null
        ) {
            throw new \InvalidArgumentException(
                'Successful recommendation generation cannot contain a failure reason.'
            );
        }

        if (
            $this->status !== self::STATUS_SUCCESS &&
            ($this->reason === null || !$this->is_valid_reason($this->reason))
        ) {
            throw new \InvalidArgumentException(
                'Skipped or failed recommendation generation requires a stable reason.'
            );
        }

        $this->recommendations = $this->normalize_recommendations(
            $recommendations
        );

        $this->validate_metadata($this->metadata);
    }

    /**
     * Create a successful result.
     *
     * A successful generator may legitimately return no recommendation.
     */
    public static function success(
        string $generatorkey,
        array $recommendations = [],
        array $metadata = []
    ): self {
        return new self(
            $generatorkey,
            self::STATUS_SUCCESS,
            $recommendations,
            null,
            $metadata
        );
    }

    /**
     * Create a skipped result.
     */
    public static function skipped(
        string $generatorkey,
        string $reason,
        array $metadata = []
    ): self {
        return new self(
            $generatorkey,
            self::STATUS_SKIPPED,
            [],
            $reason,
            $metadata
        );
    }

    /**
     * Create a failed result.
     *
     * This result is diagnostic. The orchestrator will continue with the other
     * generators instead of making the entire recommendation engine fail.
     */
    public static function failed(
        string $generatorkey,
        string $reason,
        array $metadata = []
    ): self {
        return new self(
            $generatorkey,
            self::STATUS_FAILED,
            [],
            $reason,
            $metadata
        );
    }

    /**
     * Return all supported result statuses.
     *
     * @return string[]
     */
    public static function statuses(): array {
        return [
            self::STATUS_SUCCESS,
            self::STATUS_SKIPPED,
            self::STATUS_FAILED,
        ];
    }

    /**
     * Check whether the generator completed successfully.
     */
    public function is_success(): bool {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check whether the generator was skipped.
     */
    public function is_skipped(): bool {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Check whether the generator failed.
     */
    public function is_failed(): bool {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Number of produced recommendations.
     */
    public function count(): int {
        return count($this->recommendations);
    }

    /**
     * Serialize diagnostic information.
     */
    public function to_object(): \stdClass {
        return (object)[
            'generatorkey' => $this->generatorkey,
            'status' => $this->status,
            'reason' => $this->reason,
            'count' => $this->count(),
            'recommendations' => array_map(
                static fn(Recommendation $recommendation): \stdClass =>
                    $recommendation->to_object(),
                $this->recommendations
            ),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate and normalize generated recommendations.
     *
     * @return Recommendation[]
     */
    private function normalize_recommendations(array $recommendations): array {
        $normalized = [];

        foreach ($recommendations as $recommendation) {
            if (!$recommendation instanceof Recommendation) {
                throw new \InvalidArgumentException(
                    'Recommendation generation results must contain Recommendation objects.'
                );
            }

            $normalized[] = $recommendation;
        }

        return $normalized;
    }

    /**
     * Validate a stable technical identifier.
     */
    private function is_valid_key(string $key): bool {
        return preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $key) === 1;
    }

    /**
     * Validate a stable technical reason.
     */
    private function is_valid_reason(string $reason): bool {
        return preg_match('/^[a-z][a-z0-9_.-]{1,149}$/', $reason) === 1;
    }

    /**
     * Ensure diagnostics remain serializable.
     */
    private function validate_metadata(array $metadata): void {
        foreach ($metadata as $key => $value) {
            if (
                !is_string($key) ||
                preg_match('/^[a-z][a-z0-9_]{0,49}$/', $key) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation generation metadata keys must be stable technical strings.'
                );
            }

            if (!$this->is_serializable_value($value)) {
                throw new \InvalidArgumentException(
                    'Recommendation generation metadata contains an unsupported value.'
                );
            }
        }
    }

    /**
     * Validate diagnostic values recursively.
     */
    private function is_serializable_value(mixed $value): bool {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $nestedvalue) {
            if (!$this->is_serializable_value($nestedvalue)) {
                return false;
            }
        }

        return true;
    }
}