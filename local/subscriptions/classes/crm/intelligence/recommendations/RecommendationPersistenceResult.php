<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of persisting an Recommendation Engine run.
 */
final class RecommendationPersistenceResult {

    public function __construct(
        public readonly int $receivedcount,
        public readonly int $persistedcount,
        public readonly int $failedcount,
        public readonly int $expiredcount,
        public readonly array $recommendationids = [],
        public readonly array $failures = []
    ) {
        if (
            $this->receivedcount < 0 ||
            $this->persistedcount < 0 ||
            $this->failedcount < 0 ||
            $this->expiredcount < 0
        ) {
            throw new \InvalidArgumentException(
                'Recommendation persistence counters cannot be negative.'
            );
        }
    }

    public function is_success(): bool {
        return $this->failedcount === 0;
    }

    public function to_object(): \stdClass {
        return (object)[
            'receivedcount' => $this->receivedcount,
            'persistedcount' => $this->persistedcount,
            'failedcount' => $this->failedcount,
            'expiredcount' => $this->expiredcount,
            'recommendationids' =>
                $this->recommendationids,
            'failures' => $this->failures,
            'success' => $this->is_success(),
        ];
    }
}