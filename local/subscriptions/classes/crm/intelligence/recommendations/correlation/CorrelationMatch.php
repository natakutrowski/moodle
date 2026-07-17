<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;

/**
 * Successful result returned by one correlation rule.
 */
final class CorrelationMatch {

    /**
     * @var string[]
     */
    public readonly array $matchedsignalkeys;

    /**
     * @var string[]
     */
    public readonly array $matchedrecommendationkeys;

    /**
     * @var string[]
     */
    public readonly array $suppressedrecommendationkeys;

    public readonly string $confidencelevel;

    /**
     * @param string $rulekey Stable correlation rule key.
     * @param Recommendation $recommendation Consolidated recommendation.
     * @param int $confidencescore Confidence from 0 to 100.
     * @param string[] $matchedsignalkeys Signals supporting the match.
     * @param string[] $matchedrecommendationkeys Existing recommendations supporting the match.
     * @param string[] $suppressedrecommendationkeys Recommendations hidden to reduce noise.
     * @param array $metadata Non-sensitive diagnostic metadata.
     */
    public function __construct(
        public readonly string $rulekey,
        public readonly Recommendation $recommendation,
        public readonly int $confidencescore,
        array $matchedsignalkeys = [],
        array $matchedrecommendationkeys = [],
        array $suppressedrecommendationkeys = [],
        public readonly array $metadata = []
    ) {
        if (
            preg_match(
                '/^[a-z][a-z0-9_.-]{1,99}$/',
                $this->rulekey
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Invalid correlation rule key.'
            );
        }

        if (
            !CorrelationConfidence::is_valid_score(
                $this->confidencescore
            )
        ) {
            throw new \InvalidArgumentException(
                'Correlation confidence must be between 0 and 100.'
            );
        }

        $this->matchedsignalkeys =
            $this->normalize_keys(
                $matchedsignalkeys
            );

        $this->matchedrecommendationkeys =
            $this->normalize_keys(
                $matchedrecommendationkeys
            );

        $this->suppressedrecommendationkeys =
            $this->normalize_keys(
                $suppressedrecommendationkeys
            );

        $this->confidencelevel =
            CorrelationConfidence::from_score(
                $this->confidencescore
            );

        $this->validate_metadata(
            $this->metadata
        );
    }

    public function should_suppress_components(): bool {
        return
            $this->confidencescore >= 75 &&
            $this->suppressedrecommendationkeys !== [];
    }

    public function to_object(): \stdClass {
        return (object)[
            'rulekey' => $this->rulekey,
            'confidencescore' =>
                $this->confidencescore,
            'confidencelevel' =>
                $this->confidencelevel,
            'matchedsignalkeys' =>
                $this->matchedsignalkeys,
            'matchedrecommendationkeys' =>
                $this->matchedrecommendationkeys,
            'suppressedrecommendationkeys' =>
                $this->suppressedrecommendationkeys,
            'recommendation' =>
                $this->recommendation->to_object(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @return string[]
     */
    private function normalize_keys(
        array $keys
    ): array {
        $normalized = [];

        foreach ($keys as $key) {
            if (
                !is_string($key) ||
                preg_match(
                    '/^[a-z][a-z0-9_.-]{1,149}$/',
                    $key
                ) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Invalid correlation key collection.'
                );
            }

            $normalized[$key] = $key;
        }

        return array_values($normalized);
    }

    private function validate_metadata(
        array $metadata
    ): void {
        foreach ($metadata as $key => $value) {
            if (
                !is_string($key) ||
                preg_match(
                    '/^[a-z][a-z0-9_]{0,49}$/',
                    $key
                ) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Invalid correlation metadata key.'
                );
            }

            if (
                $value !== null &&
                !is_scalar($value) &&
                !is_array($value)
            ) {
                throw new \InvalidArgumentException(
                    'Unsupported correlation metadata value.'
                );
            }
        }
    }
}