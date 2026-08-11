<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchasePaymentRequestSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchasePaymentSummary;

final class commerce_purchase_payment_request_summary_test extends advanced_testcase {
    public function test_native_payment_can_expose_linked_payment_request_details(): void {
        $request = new CommercePurchasePaymentRequestSummary(
            19,
            'subscription',
            'failed',
            'stripe',
            'EUR',
            12000,
            'cs_test_123',
            null,
            1700000000,
            1700000300,
            1700086400,
            2,
            1700000300,
            'Card declined'
        );

        $payment = new CommercePurchasePaymentSummary(
            'failed',
            'stripe',
            'pi_123',
            null,
            'EUR',
            12000,
            null,
            $request
        );

        $this->assertSame(19, $payment->paymentrequest?->id);
        $this->assertSame(2, $payment->paymentrequest?->attempts);
        $this->assertSame('Card declined', $payment->paymentrequest?->lasterror);
    }
}
