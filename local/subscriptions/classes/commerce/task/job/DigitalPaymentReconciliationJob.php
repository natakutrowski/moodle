<?php

namespace local_subscriptions\commerce\task\job;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\digital\services\DigitalPaymentReconciliationService;
final class DigitalPaymentReconciliationJob implements CommerceTaskJob {
    public function __construct(private readonly ?DigitalPaymentReconciliationService $service = null) {
    }
    public function run(): TaskExecutionResult {
        $r = new TaskExecutionResult('digital_payment_reconciliation');
        $lock = TaskLock::acquire('digital.reconciliation');
        if (!$lock) {
            $r->mark_locked();
            return $r->finish();
        }
        try {
            $data = ($this->service ?? new DigitalPaymentReconciliationService())->reconcile_pending(['limit' => 5, 'minage' => 300, 'maxage' => 2 * DAYSECS]);
            foreach ($data as $k => $v) {
                $r->increment((string)$k, (int)$v);
            }
            return $r->finish();
        } finally {
            $lock->release();
        }
    }
}
