<?php
namespace local_subscriptions\task;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\task\base\AbstractCommerceTask;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\job\StripePaymentReconciliationJob;
final class reconcile_stripe_payments_task extends AbstractCommerceTask {
    public function get_name(): string { return get_string('task_reconcile_stripe_payments','local_subscriptions'); }
    protected function create_job(): CommerceTaskJob { return new StripePaymentReconciliationJob(); }
}
