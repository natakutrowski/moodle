<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_cron_safety_test extends advanced_testcase {

    public function test_subscription_tasks_share_one_lifecycle_job(): void {
        foreach (['expire_user_enrolments_task.php', 'subscription_rollover_task.php'] as $file) {
            $source = file_get_contents(__DIR__ . '/../../../classes/task/' . $file);

            $this->assertStringContainsString('SubscriptionLifecycleJob', $source);
            $this->assertStringNotContainsString('global $DB', $source);
        }
    }

    public function test_duplicate_rollover_schedule_is_removed(): void {
        $source = file_get_contents(__DIR__ . '/../../../db/tasks.php');

        $this->assertStringNotContainsString(
            "'classname' => '\\local_subscriptions\\task\\subscription_rollover_task'",
            $source,
        );
        $this->assertStringContainsString('expire_user_enrolments_task', $source);
    }

    public function test_paid_request_repair_job_uses_canonical_pipeline(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/task/job/PaidPaymentRequestRepairJob.php',
        );

        $this->assertStringContainsString('SubscriptionPostPaymentProcessor', $source);
        $this->assertStringContainsString('PaymentService::on_checkout_completed', $source);
        $this->assertStringContainsString('CommerceDualWriteBridge::subscription', $source);
    }

    public function test_digital_reconciliation_uses_failed_event_pipeline(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/digital/services/DigitalPaymentReconciliationService.php',
        );

        $this->assertStringContainsString('digital_payment_service::on_payment_failed', $source);
        $this->assertStringContainsString("new InternalEvent('payment_failed'", $source);
    }

    public function test_access_repair_job_uses_entitlement_aware_manager(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/task/job/SubscriptionAccessRepairJob.php',
        );

        $this->assertStringContainsString(
            'subscription_manager::enrol_user_to_courses',
            $source,
        );

        $repositorysource = file_get_contents(
            __DIR__ . '/../../../classes/commerce/task/repository/SubscriptionAccessRepairRepository.php',
        );

        $this->assertMatchesRegularExpression(
            '/s\.end_date\s*=\s*0/',
            $repositorysource,
        );
        $this->assertStringNotContainsString(
            'subscription_access_scope',
            $repositorysource,
        );
    }

    public function test_paid_request_repair_quarantines_unrepairable_requests(): void {
        $jobsource = file_get_contents(
            __DIR__ . '/../../../classes/commerce/task/job/PaidPaymentRequestRepairJob.php',
        );
        $repositorysource = file_get_contents(
            __DIR__ . '/../../../classes/commerce/task/repository/PaidPaymentRequestRepairRepository.php',
        );

        $this->assertStringContainsString('quarantine_reason', $jobsource);
        $this->assertStringContainsString("increment('quarantined')", $jobsource);
        $this->assertStringContainsString('QUARANTINE_PREFIX', $repositorysource);
        $this->assertStringContainsString('last_error NOT LIKE :quarantineprefix', $repositorysource);
    }

    public function test_paid_request_repair_can_resolve_email_from_userid(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/domain/PaymentService.php');

        $this->assertMatchesRegularExpression(
            "/'id'\s*=>\s*\(int\)\s*\\\$pr->userid\s*,\s*'deleted'\s*=>\s*0/",
            $source,
        );
        $this->assertMatchesRegularExpression(
            '/\$email\s*=\s*\(string\)\s*\$linkeduser->email\s*;/',
            $source,
        );
    }
}
