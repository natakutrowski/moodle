<?php
namespace local_subscriptions\commerce\task\job;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationBatchService;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\support\TaskLock;
final class StripePaymentReconciliationJob implements CommerceTaskJob {
    public function __construct(private readonly ?StripePaymentReconciliationBatchService $service=null){}
    public function run(): TaskExecutionResult {
        global $DB; $r=new TaskExecutionResult('stripe_payment_reconciliation');
        if(!(bool)get_config('local_subscriptions','stripe_reconciliation_cron_enabled')){$r->increment('disabled');return $r->finish();}
        $lock=TaskLock::acquire('stripe.payment.reconciliation'); if(!$lock){$r->mark_locked();return $r->finish();}
        try{
            $limit=max(1,(int)(get_config('local_subscriptions','stripe_reconciliation_batch_size')?:20));
            $min=max(60,(int)(get_config('local_subscriptions','stripe_reconciliation_min_age')?:300));
            $max=max($min,(int)(get_config('local_subscriptions','stripe_reconciliation_max_age')?:172800));
            $data=($this->service??StripePaymentReconciliationBatchService::create($DB))->run($limit,$min,$max);
            foreach($data as $n=>$v)$r->increment((string)$n,(int)$v);
            return $r->finish();
        }finally{$lock->release();}
    }
}
