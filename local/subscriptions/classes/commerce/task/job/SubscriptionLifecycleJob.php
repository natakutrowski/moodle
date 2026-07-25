<?php

namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\SubscriptionLifecycleRepository;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\service\SubscriptionLifecycleService;

final class SubscriptionLifecycleJob implements CommerceTaskJob {

    public function __construct(
        private readonly ?SubscriptionLifecycleRepository $repository = null,
        private readonly ?SubscriptionLifecycleService $service = null,
        private readonly int $limit = 500,
    ) {
    }

    public function run(): TaskExecutionResult {
        $result = new TaskExecutionResult('subscription_lifecycle');
        $lock = TaskLock::acquire('subscription.lifecycle');

        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }

        try {
            $repository = $this->repository ?? new SubscriptionLifecycleRepository();
            $service = $this->service ?? new SubscriptionLifecycleService();
            $now = time();

            foreach ($repository->find_due_activation_ids($now, $this->limit) as $subscriptionid) {
                $result->increment('scanned');

                try {
                    if ($service->activate($subscriptionid, $now)) {
                        $result->increment('activated');
                    } else {
                        $result->increment('skipped');
                    }
                } catch (\Throwable $exception) {
                    $result->add_error($subscriptionid, $exception);
                }
            }

            foreach ($repository->find_due_expiration_ids($now, $this->limit) as $subscriptionid) {
                $result->increment('scanned');

                try {
                    if ($service->expire($subscriptionid, $now)) {
                        $result->increment('expired');
                    } else {
                        $result->increment('skipped');
                    }
                } catch (\Throwable $exception) {
                    $result->add_error($subscriptionid, $exception);
                }
            }

            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
