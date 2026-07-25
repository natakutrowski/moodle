<?php

namespace local_subscriptions\commerce\task\support;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\persistence\CommerceTaskRunRepository;

/**
 * Shared execution pipeline for all scheduled Commerce tasks.
 */
final class CommerceTaskRunner {

    public function __construct(
        private readonly ?CommerceTaskRunRepository $runs = null,
    ) {
    }

    public function execute(CommerceTaskJob $job): TaskExecutionResult {
        try {
            $result = $job->run()->finish();
        } catch (\Throwable $exception) {
            $result = new TaskExecutionResult($this->job_name($job));
            $result->add_error('job', $exception);
            $result->finish();
        }

        $this->persist_safely($result);
        TaskResultRenderer::trace($result);

        return $result;
    }

    private function persist_safely(TaskExecutionResult $result): void {
        try {
            $repository = $this->runs ?? new CommerceTaskRunRepository();
            $repository->store($result);

            if (random_int(1, 100) === 1) {
                $repository->purge_expired();
            }
        } catch (\Throwable $exception) {
            debugging(
                'Unable to persist Commerce cron metrics: ' . $exception->getMessage(),
                DEBUG_DEVELOPER,
            );
        }
    }

    private function job_name(CommerceTaskJob $job): string {
        $shortname = (new \ReflectionClass($job))->getShortName();
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $shortname));
    }
}
