<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\base\AbstractCommerceTask;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\job\PaidPaymentRequestRepairJob;

final class repair_paid_pr_task extends AbstractCommerceTask {

    public function get_name(): string {
        return get_string('task_repair_paid_pr', 'local_subscriptions');
    }

    protected function create_job(): CommerceTaskJob {
        return new PaidPaymentRequestRepairJob();
    }
}
