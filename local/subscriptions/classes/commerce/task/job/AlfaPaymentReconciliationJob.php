<?php

namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationBatchService;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\support\TaskLock;

/** Scheduled recovery of Alfa payments whose browser return/webhook was missed. */
final class AlfaPaymentReconciliationJob implements CommerceTaskJob {
    public function __construct(private readonly ?AlfaPaymentReconciliationBatchService $service = null) {
    }

    public function run(): TaskExecutionResult {
        global $DB;

        $result = new TaskExecutionResult('alfa_payment_reconciliation');
        if (!(bool)get_config('local_subscriptions', 'alfa_reconciliation_cron_enabled')) {
            $result->increment('disabled');
            return $result->finish();
        }

        $lock = TaskLock::acquire('alfa.payment.reconciliation');
        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }

        try {
            $limit = max(1, (int)(get_config('local_subscriptions', 'alfa_reconciliation_batch_size') ?: 20));
            $minage = max(60, (int)(get_config('local_subscriptions', 'alfa_reconciliation_min_age') ?: 300));
            $maxage = max($minage, (int)(get_config('local_subscriptions', 'alfa_reconciliation_max_age') ?: 172800));
            $data = ($this->service ?? AlfaPaymentReconciliationBatchService::create($DB))->run(
                $limit,
                $minage,
                $maxage
            );
            foreach ($data as $name => $value) {
                $result->increment((string)$name, (int)$value);
            }
            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
