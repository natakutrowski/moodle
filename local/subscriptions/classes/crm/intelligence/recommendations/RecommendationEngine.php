<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceSnapshot;
use local_subscriptions\crm\intelligence\recommendations\generators\LegacyRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\RecommendationGeneratorInterface;
use local_subscriptions\crm\intelligence\recommendations\generators\CustomerSuccessRiskRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\LearningSupportRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\PaymentRecoveryRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\PositiveProgressRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\SupportFollowUpRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\generators\WorkItemReviewRecommendationGenerator;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationEngine;
use local_subscriptions\crm\intelligence\scoring\LeadScore;

/**
 * Orchestrates all CRM recommendation generators.
 */
final class RecommendationEngine {

    /**
     * @var RecommendationGeneratorInterface[]
     */
    private readonly array $generators;

    /**
     * Cross-domain correlation engine.
     */
    private readonly CorrelationEngine
        $correlationengine;    

    /**
     * Recommendation deduplication service.
     */
    private readonly RecommendationDeduplicator $deduplicator;

    /**
     * @param RecommendationGeneratorInterface[]|null $generators
     */
    public function __construct(
        ?array $generators = null,
        ?RecommendationDeduplicator $deduplicator = null,
        ?CorrelationEngine $correlationengine = null
    ) {
        $this->deduplicator =
            $deduplicator ??
            new RecommendationDeduplicator();

        $this->correlationengine =
            $correlationengine ??
            new CorrelationEngine(
                deduplicator:
                    $this->deduplicator
            );

        $this->generators =
            $this->normalize_generators(
                $generators ??
                $this->default_generators()
            );
    }

    /**
     * Backward-compatible recommendation construction method.
     *
     * Existing callers continue receiving Recommendation[].
     *
     * @return Recommendation[]
     */
    public function build(
        CrmIntelligenceSnapshot $snapshot,
        LeadScore $score,
        array $opportunities
    ): array {
        $context = new RecommendationGenerationContext(
            snapshot: $snapshot,
            leadscore: $score,
            opportunities: $opportunities
        );

        return $this->generate($context)->recommendations;
    }

    /**
     * Execute all recommendation generators.
     */
    public function generate(
        RecommendationGenerationContext $context
    ): RecommendationEngineResult {
        $generatorresults = [];
        $rawrecommendations = [];

        foreach ($this->generators as $generator) {
            $result = $this->run_generator($generator, $context);
            $generatorresults[] = $result;

            if (!$result->is_success()) {
                continue;
            }

            foreach ($result->recommendations as $recommendation) {
                $rawrecommendations[] = $recommendation;
            }
        }

        $deduplicatedrecommendations =
            $this->deduplicator->deduplicate(
                $rawrecommendations
            );

        $correlationresult =
            $this->correlationengine->correlate(
                $context,
                $deduplicatedrecommendations
            );

        $recommendations =
            $correlationresult
                ->recommendations;

        $this->sort_recommendations(
            $recommendations
        );

        return new RecommendationEngineResult(
            recommendations:
                $recommendations,
            generatorresults:
                $generatorresults,
            generatedat:
                $context->timestamp(),
            rawcount:
                count($rawrecommendations),
            duplicatecount:
                count($rawrecommendations) -
                count(
                    $deduplicatedrecommendations
                ),
            correlationresult:
                $correlationresult
        );
    }

    /**
     * Return registered generator keys.
     *
     * Useful for diagnostics and future CLI validation.
     *
     * @return string[]
     */
    public function generator_keys(): array {
        return array_map(
            static fn(RecommendationGeneratorInterface $generator): string =>
                $generator->key(),
            $this->generators
        );
    }

    /**
     * Return registered correlation rule keys.
     *
     * @return string[]
     */
    public function correlation_rule_keys(): array {
        return $this->correlationengine
            ->rule_keys();
    }

    /**
     * Execute one generator without allowing it to break the whole engine.
     */
    private function run_generator(
        RecommendationGeneratorInterface $generator,
        RecommendationGenerationContext $context
    ): RecommendationGenerationResult {
        try {
            $result = $generator->generate($context);

            if ($result->generatorkey !== $generator->key()) {
                return RecommendationGenerationResult::failed(
                    $generator->key(),
                    'generator_result_key_mismatch',
                    [
                        'returnedkey' => $result->generatorkey,
                    ]
                );
            }

            return $result;
        } catch (\Throwable $exception) {
            debugging(
                sprintf(
                    'Recommendation generator "%s" failed: %s',
                    $generator->key(),
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );

            return RecommendationGenerationResult::failed(
                $generator->key(),
                'generator_exception',
                [
                    'exceptionclass' => get_class($exception),
                ]
            );
        }
    }

    /**
     * Return the generators enabled by default.
     *
     * Future domain generators will be registered here progressively.
     *
     * @return RecommendationGeneratorInterface[]
     */
    private function default_generators(): array {
        return [
            /*
             * Specific generators run before the remaining legacy rules.
             */
            new CustomerSuccessRiskRecommendationGenerator(),
            new PaymentRecoveryRecommendationGenerator(),
            new SupportFollowUpRecommendationGenerator(),
            new WorkItemReviewRecommendationGenerator(),
            new LearningSupportRecommendationGenerator(),
            new PositiveProgressRecommendationGenerator(),

            /*
             * Legacy commercial opportunities and first-note rule remain
             * enabled until their dedicated Phase 7 generators replace them.
             */
            new LegacyRecommendationGenerator(),
        ];
    }

    /**
     * Validate generator objects and stable keys.
     *
     * Duplicate generator keys are rejected because diagnostics and future
     * persistence use those keys as stable identifiers.
     *
     * @param array $generators
     * @return RecommendationGeneratorInterface[]
     */
    private function normalize_generators(array $generators): array {
        $normalized = [];
        $keys = [];

        foreach ($generators as $generator) {
            if (!$generator instanceof RecommendationGeneratorInterface) {
                throw new \InvalidArgumentException(
                    'Recommendation Engine generators must implement RecommendationGeneratorInterface.'
                );
            }

            $key = $generator->key();

            if (
                preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $key) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Invalid Recommendation Engine generator key.'
                );
            }

            if (isset($keys[$key])) {
                throw new \InvalidArgumentException(
                    'Duplicate Recommendation Engine generator key: ' . $key
                );
            }

            $keys[$key] = true;
            $normalized[] = $generator;
        }

        return $normalized;
    }

    /**
     * Sort final recommendations deterministically.
     */
    private function sort_recommendations(array &$recommendations): void {
        usort(
            $recommendations,
            static function (
                Recommendation $left,
                Recommendation $right
            ): int {
                $prioritycomparison = $right->priority <=> $left->priority;

                if ($prioritycomparison !== 0) {
                    return $prioritycomparison;
                }

                $typecomparison = strcmp(
                    $left->recommendationtype,
                    $right->recommendationtype
                );

                if ($typecomparison !== 0) {
                    return $typecomparison;
                }

                return strcmp($left->key, $right->key);
            }
        );
    }
}