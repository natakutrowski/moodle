<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\paypal\PayPalPaymentReconciliationBatchService;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\support\TaskLock;

final class PayPalPaymentReconciliationJob implements CommerceTaskJob {
    public function __construct(private readonly ?PayPalPaymentReconciliationBatchService $service = null) {
    }

    public function run(): TaskExecutionResult {
        global $DB;
        $result = new TaskExecutionResult('paypal_payment_reconciliation');
        if (!(bool)get_config('local_subscriptions', 'paypal_reconciliation_cron_enabled')) {
            $result->increment('disabled');
            return $result->finish();
        }
        $lock = TaskLock::acquire('paypal.payment.reconciliation');
        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }
        try {
            $limit = max(1, (int)(get_config('local_subscriptions', 'paypal_reconciliation_batch_size') ?: 20));
            $minage = max(60, (int)(get_config('local_subscriptions', 'paypal_reconciliation_min_age') ?: 300));
            $maxage = max($minage, (int)(get_config('local_subscriptions', 'paypal_reconciliation_max_age') ?: 172800));
            $data = ($this->service ?? PayPalPaymentReconciliationBatchService::create($DB))->run($limit, $minage, $maxage);
            foreach ($data as $name => $value) {
                $result->increment((string)$name, (int)$value);
            }
            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
