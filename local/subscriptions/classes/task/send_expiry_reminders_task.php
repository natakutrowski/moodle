<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\base\AbstractCommerceTask;
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\job\SubscriptionExpiryReminderJob;

final class send_expiry_reminders_task extends AbstractCommerceTask {

    public function get_name(): string {
        return get_string('task_send_expiry_reminders', 'local_subscriptions');
    }

    protected function create_job(): CommerceTaskJob {
        return new SubscriptionExpiryReminderJob();
    }
}
