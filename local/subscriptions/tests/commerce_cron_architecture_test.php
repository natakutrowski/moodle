<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_cron_architecture_test extends advanced_testcase {

    public function test_commerce_tasks_are_thin_adapters_without_database_access(): void {
        $files = [
            'expire_user_enrolments_task.php',
            'subscription_rollover_task.php',
            'repair_paid_pr_task.php',
            'reconcile_digital_payments_task.php',
            'enrol_scope_fill_task.php',
            'send_expiry_reminders_task.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents(__DIR__ . '/../classes/task/' . $file);

            $this->assertStringContainsString('extends AbstractCommerceTask', $source, $file);
            $this->assertStringNotContainsString('global $DB', $source, $file);
            $this->assertStringNotContainsString('get_records', $source, $file);
            $this->assertStringNotContainsString('update_record', $source, $file);
        }
    }

    public function test_each_commerce_task_uses_a_job(): void {
        $expected = [
            'expire_user_enrolments_task.php' => 'SubscriptionLifecycleJob',
            'subscription_rollover_task.php' => 'SubscriptionLifecycleJob',
            'repair_paid_pr_task.php' => 'PaidPaymentRequestRepairJob',
            'reconcile_digital_payments_task.php' => 'DigitalPaymentReconciliationJob',
            'enrol_scope_fill_task.php' => 'SubscriptionAccessRepairJob',
            'send_expiry_reminders_task.php' => 'SubscriptionExpiryReminderJob',
        ];

        foreach ($expected as $file => $job) {
            $source = file_get_contents(__DIR__ . '/../classes/task/' . $file);
            $this->assertStringContainsString($job, $source);
        }
    }

    public function test_duplicate_rollover_schedule_is_removed(): void {
        $source = file_get_contents(__DIR__ . '/../db/tasks.php');

        $this->assertStringNotContainsString(
            "'classname' => '\\local_subscriptions\\task\\subscription_rollover_task'",
            $source,
        );
    }

    public function test_job_foundation_exists(): void {
        $files = [
            'contract/CommerceTaskJob.php',
            'dto/TaskExecutionResult.php',
            'support/TaskResultRenderer.php',
            'support/TaskLock.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists(__DIR__ . '/../classes/commerce/task/' . $file);
        }
    }
}
