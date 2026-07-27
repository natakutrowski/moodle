<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResultException;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;

/**
 * Tests for provider-independent payment results.
 *
 * @covers \local_subscriptions\commerce\payment\result\CommercePaymentStatus
 * @covers \local_subscriptions\commerce\payment\result\CommercePaymentAction
 * @covers \local_subscriptions\commerce\payment\result\CommercePaymentResult
 */
final class commerce_payment_result_test
    extends advanced_testcase {

    public function test_redirect_result_requires_customer_action():
        void {
        $result =
            CommercePaymentResult::requires_action(
                'purchase:test',
                'stripe',
                'cs_test_123',
                CommercePaymentAction::redirect(
                    'https://checkout.example.com/session'
                )
            );

        $this->assertSame(
            CommercePaymentStatus::REQUIRES_ACTION,
            $result->get_status()
        );

        $this->assertTrue(
            $result->requires_customer_action()
        );

        $this->assertTrue(
            $result->get_action()->is_redirect()
        );
    }

    public function test_success_result_is_terminal_and_successful():
        void {
        $result =
            CommercePaymentResult::succeeded(
                'purchase:test',
                'alfa',
                'order-123'
            );

        $this->assertTrue(
            $result->is_successful()
        );

        $this->assertTrue(
            $result->is_terminal()
        );
    }

    public function test_pending_result_is_not_terminal():
        void {
        $result =
            CommercePaymentResult::pending(
                'purchase:test',
                'alfa',
                'order-123'
            );

        $this->assertFalse(
            $result->is_terminal()
        );

        $this->assertFalse(
            $result->is_successful()
        );
    }

    public function test_failed_result_requires_error_details():
        void {
        $result =
            CommercePaymentResult::failed(
                'purchase:test',
                'stripe',
                'card_declined',
                'The payment was declined.'
            );

        $this->assertTrue(
            $result->has_failed()
        );

        $this->assertSame(
            'card_declined',
            $result->get_error_code()
        );
    }

    public function test_requires_action_without_action_is_rejected():
        void {
        $this->expectException(
            CommercePaymentResultException::class
        );

        new CommercePaymentResult(
            'purchase:test',
            'stripe',
            CommercePaymentStatus::REQUIRES_ACTION,
            'cs_test_123'
        );
    }

    public function test_error_on_success_result_is_rejected():
        void {
        $this->expectException(
            CommercePaymentResultException::class
        );

        new CommercePaymentResult(
            'purchase:test',
            'stripe',
            CommercePaymentStatus::SUCCEEDED,
            'pi_test_123',
            null,
            'unexpected_error',
            'Unexpected error'
        );
    }
}