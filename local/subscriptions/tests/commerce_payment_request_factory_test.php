<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\payment\CommercePaymentRequestFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Tests the payment request factory.
 *
 * @covers \local_subscriptions\commerce\payment\CommercePaymentRequestFactory
 */
final class commerce_payment_request_factory_test
    extends advanced_testcase {

    public function test_factory_builds_request_from_preparation():
        void {
        $requestitem =
            new CommercePurchaseRequestItem(
                new CommerceItem(
                    CommerceItem::TYPE_SUBSCRIPTION,
                    'subscription-plan:14',
                    'CampusFR A1',
                    14
                ),
                1,
                12000,
                'EUR'
            );

        $purchaserequest =
            new CommercePurchaseRequest(
                'purchase:test',
                new CommerceCustomer(
                    96,
                    'student@example.com',
                    'Test',
                    'Student'
                ),
                [
                    $requestitem,
                ],
                preferredprovider: 'stripe',
                returnurl:
                    'https://example.com/payment/success',
                cancelurl:
                    'https://example.com/payment/cancel'
            );

        $prepareditem =
            new CommercePreparedPurchaseItem(
                $requestitem,
                'subscription',
                'subscription_enrolment',
                [
                    'description' =>
                        'CampusFR A1',
                ],
                [
                    'planid' => 14,
                ]
            );

        $preparation =
            new CommercePurchasePreparation(
                $purchaserequest,
                [
                    $prepareditem,
                ],
                CommercePurchaseValidationResult::valid(),
                time()
            );

        $factory =
            new CommercePaymentRequestFactory();

        $paymentrequest =
            $factory->create(
                $preparation
            );

        $this->assertSame(
            'purchase:test',
            $paymentrequest->get_reference()
        );

        $this->assertSame(
            12000,
            $paymentrequest->get_amount_minor()
        );

        $this->assertSame(
            'student@example.com',
            $paymentrequest
                ->get_customer()
                ->get_email()
        );

        $this->assertSame(
            'stripe',
            $paymentrequest
                ->get_preferred_provider()
        );

        $this->assertCount(
            1,
            $paymentrequest->get_lines()
        );

        $this->assertArrayHasKey(
            'fulfillment_operations',
            $paymentrequest->get_metadata()
        );
    }
}