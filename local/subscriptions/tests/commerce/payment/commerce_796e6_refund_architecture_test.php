<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRequest;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e6_refund_architecture_test extends advanced_testcase {
    public function test_refund_request_and_result_are_provider_independent(): void {
        $request = new CommercePaymentRefundRequest(
            'PAY.E6',
            'pi_or_order_123',
            'EUR',
            1200,
            'customer_request',
            ['purchase_reference' => 'PUR.E6']
        );

        $this->assertSame('PAY.E6', $request->get_payment_reference());
        $this->assertSame('pi_or_order_123', $request->get_provider_payment_id());
        $this->assertSame('EUR', $request->get_currency());
        $this->assertSame(1200, $request->get_amount_minor());

        $result = new CommercePaymentRefundResult(
            'example',
            'refund_123',
            CommercePaymentRefundResult::STATUS_SUCCEEDED,
            'EUR',
            1200
        );

        $this->assertSame('example', $result->get_provider_key());
        $this->assertSame('refund_123', $result->get_provider_refund_id());
        $this->assertSame(
            CommercePaymentRefundResult::STATUS_SUCCEEDED,
            $result->get_status()
        );
    }

    public function test_refund_contract_is_optional_and_does_not_break_base_provider_contract(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProvider.php'
        );
        $refundcontract = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommerceRefundCapablePaymentProvider.php'
        );

        $this->assertStringNotContainsString(
            'function refund(',
            $provider
        );
        $this->assertStringContainsString(
            'function refund(',
            $refundcontract
        );
    }

    public function test_current_stripe_and_alfa_do_not_claim_uncertified_refunds(): void {
        global $CFG;

        foreach ([
            'stripe/StripeCommercePaymentProvider.php',
            'alfa/AlfaCommercePaymentProvider.php',
        ] as $file) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/provider/'
                . $file
            );

            $this->assertStringNotContainsString(
                'implements CommerceRefundCapablePaymentProvider',
                $contents
            );
        }
    }
}
