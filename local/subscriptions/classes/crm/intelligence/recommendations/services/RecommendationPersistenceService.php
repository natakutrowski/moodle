<?php

namespace local_subscriptions\crm\intelligence\recommendations\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\RecommendationEngineResult;
use local_subscriptions\crm\intelligence\recommendations\RecommendationPersistenceResult;
use local_subscriptions\crm\intelligence\recommendations\repositories\RecommendationRepository;

/**
 * Persists complete Recommendation Engine results.
 */
final class RecommendationPersistenceService {

    public function __construct(
        private readonly RecommendationRepository $repository =
            new RecommendationRepository()
    ) {
    }

    /**
     * Persist all final recommendations from an engine run.
     *
     * One failed recommendation does not discard the other valid results.
     */
    public function persist(
        RecommendationEngineResult $result,
        bool $expireoverdue = true
    ): RecommendationPersistenceResult {
        $persistedids = [];
        $failures = [];

        foreach ($result->recommendations as $recommendation) {
            try {
                $record = $this->repository->upsert(
                    $recommendation
                );

                $persistedids[] = (int)$record->id;
            } catch (\Throwable $exception) {
                debugging(
                    sprintf(
                        'Unable to persist recommendation "%s": %s',
                        $recommendation->key,
                        $exception->getMessage()
                    ),
                    DEBUG_DEVELOPER
                );

                $failures[] = [
                    'key' => $recommendation->key,
                    'exceptionclass' =>
                        get_class($exception),
                ];
            }
        }

        $expiredcount = $expireoverdue
            ? $this->repository->expire_due(time())
            : 0;

        return new RecommendationPersistenceResult(
            receivedcount:
                count($result->recommendations),
            persistedcount:
                count($persistedids),
            failedcount:
                count($failures),
            expiredcount:
                $expiredcount,
            recommendationids:
                $persistedids,
            failures:
                $failures
        );
    }
}