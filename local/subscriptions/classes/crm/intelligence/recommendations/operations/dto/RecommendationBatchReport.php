<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationRunStatus;

/**
 * Aggregated report of one Recommendation Engine batch run.
 */
final class RecommendationBatchReport {

    /**
     * @param RecommendationBatchUserResult[] $userresults
     */
    public function __construct(
        public readonly int $runid,
        public readonly string $status,
        public readonly int $startedat,
        public readonly int $finishedat,
        public readonly int $startcursor,
        public readonly int $endcursor,
        public readonly bool $wrapped,
        public readonly int $processedcount,
        public readonly int $successcount,
        public readonly int $failedcount,
        public readonly int $generatedcount,
        public readonly int $persistedcount,
        public readonly int $duplicatecount,
        public readonly int $correlationcount,
        public readonly int $expiredcount,
        public readonly array $userresults = []
    ) {
        if ($this->runid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation run ID must be greater than zero.'
            );
        }

        if (!RecommendationRunStatus::is_valid($this->status)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation run status.'
            );
        }

        if (
            $this->startedat <= 0 ||
            $this->finishedat < $this->startedat
        ) {
            throw new \InvalidArgumentException(
                'Invalid recommendation run timestamps.'
            );
        }

        foreach ($this->userresults as $result) {
            if (
                !$result instanceof
                RecommendationBatchUserResult
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation batch report requires RecommendationBatchUserResult objects.'
                );
            }
        }
    }

    public function duration_seconds(): int {
        return max(
            0,
            $this->finishedat -
            $this->startedat
        );
    }

    public function is_success(): bool {
        return $this->status ===
            RecommendationRunStatus::COMPLETED;
    }

    public function to_object(): \stdClass {
        return (object)[
            'runid' => $this->runid,
            'status' => $this->status,
            'startedat' => $this->startedat,
            'finishedat' => $this->finishedat,
            'durationseconds' =>
                $this->duration_seconds(),
            'startcursor' => $this->startcursor,
            'endcursor' => $this->endcursor,
            'wrapped' => $this->wrapped,
            'processedcount' =>
                $this->processedcount,
            'successcount' =>
                $this->successcount,
            'failedcount' =>
                $this->failedcount,
            'generatedcount' =>
                $this->generatedcount,
            'persistedcount' =>
                $this->persistedcount,
            'duplicatecount' =>
                $this->duplicatecount,
            'correlationcount' =>
                $this->correlationcount,
            'expiredcount' =>
                $this->expiredcount,
            'userresults' => array_map(
                static fn(
                    RecommendationBatchUserResult $result
                ): array => $result->to_array(),
                $this->userresults
            ),
        ];
    }
}