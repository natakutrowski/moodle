<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\base\AbstractCommerceTask;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\job\SubscriptionAccessRepairJob;

final class enrol_scope_fill_task extends AbstractCommerceTask {

    public function get_name(): string {
        return get_string('task_enrol_scope_fill', 'local_subscriptions');
    }

    protected function create_job(): CommerceTaskJob {
        return new SubscriptionAccessRepairJob();
    }
}
