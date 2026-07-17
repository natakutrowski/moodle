<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationEngineResult;

/**
 * Immutable result of a complete Recommendation Engine run.
 */
final class RecommendationEngineResult {

    /**
     * @var Recommendation[]
     */
    public readonly array $recommendations;

    /**
     * @var RecommendationGenerationResult[]
     */
    public readonly array $generatorresults;

    /**
     * @param Recommendation[] $recommendations Final merged recommendations.
     * @param RecommendationGenerationResult[] $generatorresults Generator diagnostics.
     * @param int $generatedat Generation timestamp.
     * @param int $rawcount Number of recommendations before deduplication.
     * @param int $duplicatecount Number of removed duplicates.
     */
    public function __construct(
        array $recommendations,
        array $generatorresults,
        public readonly int $generatedat,
        public readonly int $rawcount,
        public readonly int $duplicatecount,
        public readonly ?CorrelationEngineResult $correlationresult = null
    ) {
        if ($this->generatedat <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation Engine timestamp must be greater than zero.'
            );
        }

        if ($this->rawcount < 0 || $this->duplicatecount < 0) {
            throw new \InvalidArgumentException(
                'Recommendation Engine counters cannot be negative.'
            );
        }

        $this->recommendations = $this->validate_recommendations(
            $recommendations
        );

        $this->generatorresults = $this->validate_generator_results(
            $generatorresults
        );
    }

    /**
     * Number of final recommendations.
     */
    public function count(): int {
        return count($this->recommendations);
    }

    /**
     * Number of successful generators.
     */
    public function successful_generator_count(): int {
        return count(array_filter(
            $this->generatorresults,
            static fn(RecommendationGenerationResult $result): bool =>
                $result->is_success()
        ));
    }

    /**
     * Number of failed generators.
     */
    public function failed_generator_count(): int {
        return count(array_filter(
            $this->generatorresults,
            static fn(RecommendationGenerationResult $result): bool =>
                $result->is_failed()
        ));
    }

    /**
     * Whether at least one generator failed.
     */
    public function has_failures(): bool {
        return $this->failed_generator_count() > 0;
    }

    /**
     * Serialize this engine result.
     */
    public function to_object(): \stdClass {
        return (object)[
            'generatedat' => $this->generatedat,
            'count' => $this->count(),
            'rawcount' => $this->rawcount,
            'duplicatecount' => $this->duplicatecount,
            'successfulgeneratorcount' => $this->successful_generator_count(),
            'failedgeneratorcount' => $this->failed_generator_count(),
            'hasfailures' => $this->has_failures(),
            'correlation' =>
                $this->correlationresult?->to_object(),            
            'recommendations' => array_map(
                static fn(Recommendation $recommendation): \stdClass =>
                    $recommendation->to_object(),
                $this->recommendations
            ),
            'generatorresults' => array_map(
                static fn(RecommendationGenerationResult $result): \stdClass =>
                    $result->to_object(),
                $this->generatorresults
            ),
        ];
    }

    /**
     * @return Recommendation[]
     */
    private function validate_recommendations(array $recommendations): array {
        foreach ($recommendations as $recommendation) {
            if (!$recommendation instanceof Recommendation) {
                throw new \InvalidArgumentException(
                    'Recommendation Engine results must contain Recommendation objects.'
                );
            }
        }

        return array_values($recommendations);
    }

    /**
     * @return RecommendationGenerationResult[]
     */
    private function validate_generator_results(array $results): array {
        foreach ($results as $result) {
            if (!$result instanceof RecommendationGenerationResult) {
                throw new \InvalidArgumentException(
                    'Recommendation Engine generator results must contain RecommendationGenerationResult objects.'
                );
            }
        }

        return array_values($results);
    }
}