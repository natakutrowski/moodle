<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;
use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationRunStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\dto\RecommendationBatchReport;
use local_subscriptions\crm\intelligence\recommendations\operations\dto\RecommendationBatchUserResult;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationCandidateRepository;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationCursorStore;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationRunRepository;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationLifecycleService;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationPersistenceService;
use local_subscriptions\crm\intelligence\runtime\CrmComputationContext;
use local_subscriptions\crm\intelligence\runtime\CrmUserComputationService;

/**
 * Executes the complete Recommendation Engine pipeline for CRM users.
 */
final class RecommendationBatchRunner {

    public function __construct(
        private readonly RecommendationCandidateRepository $candidates =
            new RecommendationCandidateRepository(),
        private readonly RecommendationCursorStore $cursor =
            new RecommendationCursorStore(),
        private readonly RecommendationRunRepository $runs =
            new RecommendationRunRepository(),
        private readonly RecommendationPersistenceService $persistence =
            new RecommendationPersistenceService(),
        private readonly RecommendationLifecycleService $lifecycle =
            new RecommendationLifecycleService(),
        private readonly CrmUserComputationService $computation =
            new CrmUserComputationService()
    ) {
    }

    public function run(
        int $limit =
            RecommendationBatchLimits::DEFAULT_USER_LIMIT,
        string $source = 'scheduled_task',
        bool $resetcursor = false
    ): RecommendationBatchReport {
        $limit =
            RecommendationBatchLimits::
                normalize_limit($limit);

        if ($resetcursor) {
            $this->cursor->reset();
        }

        $startedat = time();
        $startmicrotime = microtime(true);
        $startcursor = $this->cursor->get();

        $runid = $this->runs->start(
            $source,
            $startcursor,
            $limit
        );

        $context =
            CrmComputationContext::from_run(
                runid: (string)$runid,
                source: $source,
                startedat: $startedat
            );

        $userresults = [];
        $processedcount = 0;
        $successcount = 0;
        $failedcount = 0;
        $generatedcount = 0;
        $persistedcount = 0;
        $duplicatecount = 0;
        $correlationcount = 0;
        $endcursor = $startcursor;
        $wrapped = false;
        $failures = [];

        try {
            $users = $this->candidates->get_after(
                $startcursor,
                $limit
            );

            /*
             * End of candidate space:
             * wrap once to the beginning.
             */
            if (
                $users === [] &&
                $startcursor > 0
            ) {
                $wrapped = true;
                $endcursor = 0;

                $users = $this->candidates->get_after(
                    0,
                    $limit
                );
            }

            foreach ($users as $user) {
                if ($processedcount >= $limit) {
                    break;
                }

                $userstarted =
                    microtime(true);

                $userid = (int)$user->id;
                $endcursor = $userid;
                $processedcount++;

                try {
                    $computation =
                        $this->computation->compute(
                            user: $user,
                            context: $context,
                            withtrend: false
                        );

                    $engineresult =
                        $computation->recommendationresult;

                    $persistenceresult =
                        $this->persistence->persist(
                            $engineresult,
                            false
                        );

                    $usercorrelationcount =
                        $engineresult
                            ->correlationresult
                            ?->match_count() ?? 0;

                    $successcount++;
                    $generatedcount +=
                        $engineresult->count();

                    $persistedcount +=
                        $persistenceresult
                            ->persistedcount;

                    $duplicatecount +=
                        $engineresult
                            ->duplicatecount;

                    $correlationcount +=
                        $usercorrelationcount;

                    $userresults[] =
                        new RecommendationBatchUserResult(
                            userid: $userid,
                            status:
                                RecommendationBatchUserResult::
                                    STATUS_SUCCESS,
                            generatedcount:
                                $engineresult->count(),
                            persistedcount:
                                $persistenceresult
                                    ->persistedcount,
                            duplicatecount:
                                $engineresult
                                    ->duplicatecount,
                            correlationcount:
                                $usercorrelationcount,
                            durationms:
                                $this->duration_ms(
                                    $userstarted
                                )
                        );
                } catch (\Throwable $exception) {
                    $failedcount++;

                    $userresult =
                        new RecommendationBatchUserResult(
                            userid: $userid,
                            status:
                                RecommendationBatchUserResult::
                                    STATUS_FAILED,
                            reason:
                                'user_processing_failed',
                            exceptionclass:
                                get_class($exception),
                            durationms:
                                $this->duration_ms(
                                    $userstarted
                                )
                        );

                    $userresults[] = $userresult;
                    $failures[] =
                        $userresult->to_array();

                    debugging(
                        sprintf(
                            'Recommendation batch run %s failed for user %d: %s',
                            $context->runid,
                            $userid,
                            $exception->getMessage()
                        ),
                        DEBUG_DEVELOPER
                    );
                }
            }

            if ($processedcount > 0) {
                $this->cursor->save(
                    $endcursor
                );
            } else if ($wrapped) {
                $this->cursor->reset();
            }

            $expiredcount =
                $this->lifecycle->expire_due();

            $finishedat = time();

            $status = $failedcount === 0
                ? RecommendationRunStatus::COMPLETED
                : (
                    $successcount > 0
                        ? RecommendationRunStatus::PARTIAL
                        : RecommendationRunStatus::FAILED
                );

            $report =
                new RecommendationBatchReport(
                    runid: $runid,
                    status: $status,
                    startedat: $startedat,
                    finishedat: $finishedat,
                    startcursor: $startcursor,
                    endcursor: $endcursor,
                    wrapped: $wrapped,
                    processedcount:
                        $processedcount,
                    successcount:
                        $successcount,
                    failedcount:
                        $failedcount,
                    generatedcount:
                        $generatedcount,
                    persistedcount:
                        $persistedcount,
                    duplicatecount:
                        $duplicatecount,
                    correlationcount:
                        $correlationcount,
                    expiredcount:
                        $expiredcount,
                    userresults:
                        $userresults
                );

            $this->runs->finish(
                $report,
                $failures
            );

            $this->runs->mark_abandoned_runs();
            $this->runs->cleanup();

            return $report;
        } catch (\Throwable $exception) {
            $this->runs->mark_failed(
                $runid,
                'batch_execution_failed',
                get_class($exception)
            );

            throw $exception;
        }
    }

    private function duration_ms(
        float $started
    ): int {
        return max(
            0,
            (int)round(
                (microtime(true) - $started) *
                1000
            )
        );
    }
}