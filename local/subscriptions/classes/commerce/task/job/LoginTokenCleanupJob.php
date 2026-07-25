<?php
namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\LoginTokenCleanupRepository;
use local_subscriptions\commerce\task\support\TaskLock;

final class LoginTokenCleanupJob implements CommerceTaskJob {
    public function __construct(
        private readonly ?LoginTokenCleanupRepository $repository = null
    ) {
    }

    public function run(): TaskExecutionResult {
        $result = new TaskExecutionResult('login_token_cleanup');
        $lock = TaskLock::acquire('payment.login_token_cleanup');

        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }

        try {
            $repository = $this->repository ?? new LoginTokenCleanupRepository();
            $now = time();
            $count = $repository->count_expired($now);
            $result->increment('scanned', $count);

            if ($count === 0) {
                $result->increment('skipped');
                return $result->finish();
            }

            $repository->clear_expired($now);
            $result->increment('cleaned', $count);
            return $result->finish();
        } catch (\Throwable $exception) {
            $result->add_error('batch', $exception);
            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
