<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines allowed recommendation lifecycle transitions.
 */
final class RecommendationLifecycle {

    /**
     * Check whether a status transition is allowed.
     */
    public function can_transition(
        string $from,
        string $to
    ): bool {
        if (
            !RecommendationStatus::is_valid($from) ||
            !RecommendationStatus::is_valid($to)
        ) {
            return false;
        }

        if ($from === $to) {
            return false;
        }

        $allowed = [
            RecommendationStatus::PROPOSED => [
                RecommendationStatus::ACCEPTED,
                RecommendationStatus::DISMISSED,
                RecommendationStatus::COMPLETED,
                RecommendationStatus::EXPIRED,
            ],

            RecommendationStatus::ACCEPTED => [
                RecommendationStatus::DISMISSED,
                RecommendationStatus::COMPLETED,
                RecommendationStatus::EXPIRED,
            ],

            RecommendationStatus::DISMISSED => [],

            RecommendationStatus::COMPLETED => [],

            RecommendationStatus::EXPIRED => [],
        ];

        return in_array(
            $to,
            $allowed[$from] ?? [],
            true
        );
    }

    /**
     * Assert that a transition is allowed.
     */
    public function require_transition(
        string $from,
        string $to
    ): void {
        if (!$this->can_transition($from, $to)) {
            throw new \DomainException(
                sprintf(
                    'Recommendation transition from "%s" to "%s" is not allowed.',
                    $from,
                    $to
                )
            );
        }
    }
}