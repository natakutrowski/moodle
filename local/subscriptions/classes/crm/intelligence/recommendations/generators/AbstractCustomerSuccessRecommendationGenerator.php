<?php

namespace local_subscriptions\crm\intelligence\recommendations\generators;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationEvidence;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationSource;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationContextBuilder;
use local_subscriptions\crm\success\dto\CustomerSuccessResult;
use local_subscriptions\crm\success\signals\SuccessSignal;

/**
 * Shared helpers for deterministic Customer Success recommendation generators.
 */
abstract class AbstractCustomerSuccessRecommendationGenerator implements
    RecommendationGeneratorInterface {

    /**
     * Return Customer Success data or null when unavailable.
     */
    final protected function success_result(
        RecommendationGenerationContext $context
    ): ?CustomerSuccessResult {
        return RecommendationContextBuilder::customer_success(
            $context
        );
    }

    /**
     * Return signals matching one or more categories.
     *
     * @param CustomerSuccessResult $result
     * @param string[] $categories
     * @return SuccessSignal[]
     */
    final protected function signals_by_categories(
        CustomerSuccessResult $result,
        array $categories
    ): array {
        return array_values(array_filter(
            $result->signals->all(),
            static fn(SuccessSignal $signal): bool =>
                in_array(
                    $signal->category,
                    $categories,
                    true
                )
        ));
    }

    /**
     * Return signals matching exact stable keys.
     *
     * @param CustomerSuccessResult $result
     * @param string[] $keys
     * @return SuccessSignal[]
     */
    final protected function signals_by_keys(
        CustomerSuccessResult $result,
        array $keys
    ): array {
        return array_values(array_filter(
            $result->signals->all(),
            static fn(SuccessSignal $signal): bool =>
                in_array(
                    $signal->key,
                    $keys,
                    true
                )
        ));
    }

    /**
     * Return signals whose key starts with a stable prefix.
     *
     * @return SuccessSignal[]
     */
    final protected function signals_by_prefix(
        CustomerSuccessResult $result,
        string $prefix
    ): array {
        return array_values(array_filter(
            $result->signals->all(),
            static fn(SuccessSignal $signal): bool =>
                str_starts_with(
                    $signal->key,
                    $prefix
                )
        ));
    }

    /**
     * Return only negative signals.
     *
     * @param SuccessSignal[] $signals
     * @return SuccessSignal[]
     */
    final protected function negative_signals(
        array $signals
    ): array {
        return array_values(array_filter(
            $signals,
            static fn(SuccessSignal $signal): bool =>
                $signal->weight < 0
        ));
    }

    /**
     * Return only positive signals.
     *
     * @param SuccessSignal[] $signals
     * @return SuccessSignal[]
     */
    final protected function positive_signals(
        array $signals
    ): array {
        return array_values(array_filter(
            $signals,
            static fn(SuccessSignal $signal): bool =>
                $signal->weight > 0
        ));
    }

    /**
     * Convert Customer Success signals into recommendation evidence.
     *
     * @param SuccessSignal[] $signals
     * @return RecommendationEvidence[]
     */
    final protected function evidence_from_signals(
        array $signals
    ): array {
        $evidence = [];

        foreach ($signals as $signal) {
            if (!$signal instanceof SuccessSignal) {
                throw new \InvalidArgumentException(
                    'Customer Success recommendation evidence requires SuccessSignal objects.'
                );
            }

            $evidence[] = new RecommendationEvidence(
                RecommendationSource::CUSTOMER_SUCCESS,
                $signal->key,
                $signal->value,
                min(
                    100,
                    abs($signal->weight)
                ),
                $signal->detectedat,
                [
                    'category' => $signal->category,
                    'polarity' => $signal->polarity,
                    'signalweight' => $signal->weight,
                    'metricidentities' =>
                        $signal->metricidentities,
                    'signalcontext' =>
                        $signal->context,
                ]
            );
        }

        return $evidence;
    }

    /**
     * Calculate a recommendation priority from several signals.
     *
     * Priority depends on:
     * - the strongest signal;
     * - the number of corroborating signals;
     * - an optional domain baseline.
     *
     * @param SuccessSignal[] $signals
     */
    final protected function priority_from_signals(
        array $signals,
        int $baseline = 50
    ): int {
        if ($signals === []) {
            return max(
                0,
                min(100, $baseline)
            );
        }

        $strongest = 0;

        foreach ($signals as $signal) {
            if (!$signal instanceof SuccessSignal) {
                continue;
            }

            $strongest = max(
                $strongest,
                abs($signal->weight)
            );
        }

        $correlationbonus = min(
            20,
            max(0, count($signals) - 1) * 5
        );

        return max(
            0,
            min(
                100,
                max($baseline, $strongest) +
                    $correlationbonus
            )
        );
    }

    /**
     * Return a limited set of the most significant signals.
     *
     * @param SuccessSignal[] $signals
     * @return SuccessSignal[]
     */
    final protected function strongest_signals(
        array $signals,
        int $limit = 5
    ): array {
        usort(
            $signals,
            static fn(
                SuccessSignal $left,
                SuccessSignal $right
            ): int =>
                abs($right->weight) <=>
                abs($left->weight)
        );

        return array_slice(
            $signals,
            0,
            max(1, $limit)
        );
    }

    /**
     * Stable metadata shared by successful generator results.
     */
    final protected function result_metadata(
        CustomerSuccessResult $result,
        array $signals
    ): array {
        return [
            'userid' => $result->userid,
            'signalcount' => count($signals),
            'globalscore' => $result->score->global,
            'healthlevel' => $result->score->level,
            'customerdataavailable' =>
                $result->has_data(),
            'customersuccessful' =>
                $result->is_successful(),
        ];
    }
}