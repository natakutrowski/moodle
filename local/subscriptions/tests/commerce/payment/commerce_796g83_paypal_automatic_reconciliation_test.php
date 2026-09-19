<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g83_paypal_automatic_reconciliation_test extends \advanced_testcase {
    public function test_paypal_scheduled_reconciliation_is_registered_and_configurable(): void {
        global $CFG;
        $tasks = file_get_contents($CFG->dirroot . '/local/subscriptions/db/tasks.php');
        $section = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php');
        $job = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/task/job/PayPalPaymentReconciliationJob.php');
        $batch = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/payment/reconciliation/paypal/PayPalPaymentReconciliationBatchService.php');
        $this->assertStringContainsString('reconcile_paypal_payments_task', $tasks);
        $this->assertStringContainsString("'minute' => '*/5'", $tasks);
        $this->assertStringContainsString('paypal_reconciliation_cron_enabled', $section);
        $this->assertStringContainsString('paypal.payment.reconciliation', $job);
        $this->assertStringContainsString('PayPalPaymentReconciliationBatchService', $job);
        $this->assertStringContainsString('Provider::PAYPAL', $batch);
        $this->assertStringContainsString('CommercePaymentAttemptStatus::PENDING', $batch);
        $this->assertStringContainsString("'payment_pending'", $batch);
    }

    public function test_alfa_connection_test_loads_moodle_http_client(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/CommercePaymentProviderConnectionTestService.php');
        $this->assertStringContainsString("require_once(\$CFG->libdir . '/filelib.php')", $source);
        $this->assertStringContainsString('new \\curl()', $source);
    }
}
