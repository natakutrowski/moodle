<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Deduplicates recommendations produced by several generators.
 *
 * Recommendations are grouped by their stable fingerprint. When several
 * generators identify the same situation, the most operationally useful
 * recommendation is retained.
 */
final class RecommendationDeduplicator {

    /**
     * Deduplicate recommendations.
     *
     * Selection order:
     * 1. highest numerical priority;
     * 2. greatest number of supporting evidence items;
     * 3. greatest number of contributing sources;
     * 4. greatest number of proposed actions;
     * 5. earliest input occurrence.
     *
     * @param Recommendation[] $recommendations
     * @return Recommendation[]
     */
    public function deduplicate(array $recommendations): array {
        $indexed = [];

        foreach ($recommendations as $recommendation) {
            if (!$recommendation instanceof Recommendation) {
                throw new \InvalidArgumentException(
                    'Recommendation deduplication requires Recommendation objects.'
                );
            }

            $fingerprint = $recommendation->fingerprint();

            if (!isset($indexed[$fingerprint])) {
                $indexed[$fingerprint] = $recommendation;
                continue;
            }

            if (
                $this->is_better(
                    $recommendation,
                    $indexed[$fingerprint]
                )
            ) {
                $indexed[$fingerprint] = $recommendation;
            }
        }

        return array_values($indexed);
    }

    /**
     * Determine whether one duplicate is operationally richer.
     */
    private function is_better(
        Recommendation $candidate,
        Recommendation $current
    ): bool {
        if ($candidate->priority !== $current->priority) {
            return $candidate->priority > $current->priority;
        }

        if (count($candidate->evidence) !== count($current->evidence)) {
            return count($candidate->evidence) >
                count($current->evidence);
        }

        if (count($candidate->sources) !== count($current->sources)) {
            return count($candidate->sources) >
                count($current->sources);
        }

        if (count($candidate->actions) !== count($current->actions)) {
            return count($candidate->actions) >
                count($current->actions);
        }

        return false;
    }
}