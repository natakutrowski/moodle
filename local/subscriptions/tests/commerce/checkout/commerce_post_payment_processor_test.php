<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\postpayment\CommercePostPaymentProcessingResult;
use local_subscriptions\commerce\postpayment\DigitalPostPaymentProcessor;

/**
 * Architecture and decision tests for phase 7.93G.4/G.5A.
 */
final class commerce_post_payment_processor_test extends advanced_testcase {

    public function test_processing_result_distinguishes_legacy_and_commerce(): void {
        $legacy = CommercePostPaymentProcessingResult::legacy_required(12);
        $completed = CommercePostPaymentProcessingResult::commerce_completed(12);
        $already = CommercePostPaymentProcessingResult::already_processed(12);

        $this->assertTrue($legacy->requires_legacy());
        $this->assertFalse($legacy->is_handled());
        $this->assertFalse($completed->requires_legacy());
        $this->assertTrue($completed->is_handled());
        $this->assertTrue($already->is_handled());
        $this->assertSame(12, $completed->get_payment_request_id());
    }

    public function test_post_payment_processor_is_available(): void {
        $this->assertTrue(class_exists(DigitalPostPaymentProcessor::class));
    }

    public function test_event_router_uses_controlled_post_payment_processor(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/payment/EventRouter.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'DigitalPostPaymentProcessor',
            $source
        );

        $this->assertStringContainsString(
            '->process($event)',
            $source
        );

        $this->assertStringNotContainsString(
            'after_legacy',
            $source
        );

        $this->assertStringNotContainsString(
            'before_legacy',
            $source
        );
    }

    public function test_digital_post_action_persists_receipt_idempotency_flag(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/postaction/DigitalEmailPostFulfillmentAction.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("'receipt_sent',", $source);
        $this->assertStringContainsString("'receipt_sent' => 1", $source);
        $this->assertStringContainsString('Digital emails were already sent.', $source);
    }
}
