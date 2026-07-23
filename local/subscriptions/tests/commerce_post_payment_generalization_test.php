<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\audit\runtime\CommerceRuntimeAuditReport;
use local_subscriptions\commerce\postpayment\CommercePostPaymentProcessingResult;
use local_subscriptions\commerce\postpayment\SubscriptionPostPaymentProcessor;

final class commerce_post_payment_generalization_test extends advanced_testcase {

    public function test_subscription_processor_and_runtime_audit_are_available(): void {
        $this->assertTrue(class_exists(SubscriptionPostPaymentProcessor::class));
        $this->assertTrue(class_exists(\local_subscriptions\commerce\audit\runtime\CommerceRuntimeAuditor::class));
    }

    public function test_audit_status_is_derived_from_issue_severity(): void {
        $ready = new CommerceRuntimeAuditReport([], []);
        $warning = new CommerceRuntimeAuditReport([], [[
            'severity' => 'warning', 'code' => 'warning', 'message' => 'warning',
        ]]);
        $blocked = new CommerceRuntimeAuditReport([], [[
            'severity' => 'error', 'code' => 'error', 'message' => 'error',
        ]]);

        $this->assertSame('READY', $ready->get_status());
        $this->assertSame('READY_WITH_WARNINGS', $warning->get_status());
        $this->assertSame('BLOCKED', $blocked->get_status());
    }

    public function test_post_payment_decision_does_not_request_legacy_after_completion(): void {
        $result = CommercePostPaymentProcessingResult::commerce_completed(42);
        $this->assertTrue($result->is_handled());
        $this->assertFalse($result->requires_legacy());
    }
}
