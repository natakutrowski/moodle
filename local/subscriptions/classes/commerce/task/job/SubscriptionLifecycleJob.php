<?php

namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\SubscriptionLifecycleRepository;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\service\SubscriptionLifecycleService;
use local_subscriptions\trial\TrialLegacyExpiryService;

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

            // 7.98D4 — Date B is a global ceiling for Legacy Trial access.
            // This runs inside the already-hourly lifecycle task, so no new
            // scheduled-task registration or environment-specific cron setup is needed.
            try {
                $legacy = TrialLegacyExpiryService::create()->expire_due($now);
                if (!empty($legacy['due'])) {
                    $result->increment('legacy_trial_subscriptions_expired', (int)$legacy['subscriptions_expired']);
                    $result->increment('legacy_trial_roles_removed', (int)$legacy['trial_roles_removed']);
                    $result->increment('legacy_trial_enrolments_suspended', (int)$legacy['enrolments_suspended']);
                    $result->increment('legacy_trial_courses_preserved', (int)$legacy['courses_preserved']);
                }
            } catch (\Throwable $exception) {
                $result->add_error('legacy_trial_date_b', $exception);
            }

            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
