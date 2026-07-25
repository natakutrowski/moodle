<?php

namespace local_subscriptions\commerce\task\job;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\SubscriptionAccessRepairRepository;
use local_subscriptions\commerce\task\support\TaskLock;
final class SubscriptionAccessRepairJob implements CommerceTaskJob {
    public function __construct(private readonly ?SubscriptionAccessRepairRepository $repository = null, private readonly int $limit = 500) {
    }
    public function run(): TaskExecutionResult {
        $r = new TaskExecutionResult('subscription_access_repair');
        $lock = TaskLock::acquire('subscription.access_repair');
        if (!$lock) {
            $r->mark_locked();
            return $r->finish();
        }
        try {
            $repo = $this->repository ?? new SubscriptionAccessRepairRepository();
            foreach ($repo->find_active_ids(time(), $this->limit) as $id) {
                $r->increment('scanned');
                try {
                    $s = $repo->find($id);
                    if (!$s) {
                        $r->increment('skipped');
                        continue;
                    }
                    \local_subscriptions\subscription_manager::enrol_user_to_courses((int)$s->userid, (int)$s->planid, (int)$s->start_date, (int)$s->end_date);
                    $r->increment('repaired');
                } catch (\Throwable $e) {
                    $r->add_error($id, $e);
                }
            }
            return $r->finish();
        } finally {
            $lock->release();
        }
    }
}
