<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationIssue;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Tests for PurchaseHandler value objects.
 *
 * @covers \local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationIssue
 * @covers \local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult
 * @covers \local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem
 */
final class commerce_purchase_handler_contract_test
    extends advanced_testcase {

    public function test_validation_result_can_contain_warnings_and_errors():
        void {
        $result =
            CommercePurchaseValidationResult::valid();

        $result->add_warning(
            'customer_profile_incomplete',
            'The customer profile is incomplete.'
        );

        $this->assertTrue(
            $result->is_valid()
        );

        $this->assertTrue(
            $result->has_warnings()
        );

        $result->add_error(
            'item_unavailable',
            'The requested item is unavailable.'
        );

        $this->assertFalse(
            $result->is_valid()
        );

        $this->assertCount(
            1,
            $result->get_errors()
        );

        $this->assertCount(
            1,
            $result->get_warnings()
        );
    }

    public function test_prepared_item_exposes_payment_and_fulfillment_data():
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

        $prepared =
            new CommercePreparedPurchaseItem(
                $requestitem,
                'subscription',
                'subscription_enrolment',
                [
                    'description' => 'CampusFR A1',
                ],
                [
                    'planid' => 14,
                ]
            );

        $this->assertSame(
            'subscription',
            $prepared->get_handler_key()
        );

        $this->assertSame(
            'subscription_enrolment',
            $prepared->get_fulfillment_key()
        );

        $this->assertSame(
            12000,
            $prepared->get_total_amount_minor()
        );

        $this->assertSame(
            [
                'planid' => 14,
            ],
            $prepared->get_fulfillment_metadata()
        );
    }
}