<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationRunRepository;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationBatchRunner;

/**
 * Periodically refreshes persistent CRM recommendations.
 */
final class run_crm_recommendations_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_run_crm_recommendations',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $lockfactory =
            \core\lock\lock_config::
                get_lock_factory(
                    'local_subscriptions_recommendations'
                );

        $lock = $lockfactory->get_lock(
            'recommendation_batch',
            RecommendationBatchLimits::
                LOCK_TIMEOUT_SECONDS
        );

        if (!$lock) {
            (new RecommendationRunRepository())
                ->mark_skipped(
                    'scheduled_task',
                    'concurrent_run'
                );

            mtrace(
                'CRM recommendation run skipped: another run is active.'
            );

            return;
        }

        try {
            $report =
                (new RecommendationBatchRunner())
                    ->run(
                        RecommendationBatchLimits::
                            DEFAULT_USER_LIMIT,
                        'scheduled_task'
                    );

            mtrace(
                sprintf(
                    'CRM recommendations: processed=%d success=%d failed=%d generated=%d persisted=%d correlations=%d expired=%d status=%s',
                    $report->processedcount,
                    $report->successcount,
                    $report->failedcount,
                    $report->generatedcount,
                    $report->persistedcount,
                    $report->correlationcount,
                    $report->expiredcount,
                    $report->status
                )
            );
        } finally {
            $lock->release();
        }
    }
}