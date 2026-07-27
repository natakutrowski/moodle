<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialNature;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Tests for Commerce financial classification.
 *
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseFinancialNature
 */
final class commerce_financial_classifier_test
    extends advanced_testcase {

    public function test_positive_amount_is_paid(): void {
        $purchase = $this->create_purchase(
            12000
        );

        $classifier =
            new CommercePurchaseFinancialClassifier();

        $this->assertSame(
            CommercePurchaseFinancialNature::PAID,
            $classifier->classify($purchase)
        );
    }

    public function test_zero_amount_trial_is_legitimate(): void {
        $purchase = $this->create_purchase(
            0,
            [
                'is_trial' => true,
            ]
        );

        $classifier =
            new CommercePurchaseFinancialClassifier();

        $this->assertSame(
            CommercePurchaseFinancialNature::TRIAL,
            $classifier->classify($purchase)
        );

        $this->assertTrue(
            $classifier->is_legitimate_zero_amount(
                $purchase
            )
        );
    }

    public function test_full_discount_is_legitimate(): void {
        $purchase = $this->create_purchase(
            0,
            [
                'list_price' => 120.00,
                'discount_percent' => 100,
            ]
        );

        $classifier =
            new CommercePurchaseFinancialClassifier();

        $this->assertSame(
            CommercePurchaseFinancialNature::FULL_DISCOUNT,
            $classifier->classify($purchase)
        );
    }

    public function test_complimentary_reason_is_recognised(): void {
        $purchase = $this->create_purchase(
            0,
            [
                'discount_reason' => 'Geste commercial',
            ]
        );

        $classifier =
            new CommercePurchaseFinancialClassifier();

        $this->assertSame(
            CommercePurchaseFinancialNature::COMPLIMENTARY,
            $classifier->classify($purchase)
        );
    }

    public function test_unknown_zero_amount_remains_visible(): void {
        $purchase = $this->create_purchase(
            0
        );

        $classifier =
            new CommercePurchaseFinancialClassifier();

        $this->assertSame(
            CommercePurchaseFinancialNature::
                ZERO_AMOUNT_UNCLASSIFIED,
            $classifier->classify($purchase)
        );

        $this->assertFalse(
            $classifier->is_legitimate_zero_amount(
                $purchase
            )
        );
    }

    private function create_purchase(
        int $amountminor,
        array $metadata = []
    ): SubscriptionPurchase {
        return new SubscriptionPurchase(
            'subscription:123',
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'subscription-plan:14',
                'CampusFR A1',
                14
            ),
            new CommercePayment(
                $amountminor,
                'EUR',
                CommercePayment::STATUS_COMPLETED,
                'stripe'
            ),
            96,
            'student@example.com',
            'active',
            123,
            14,
            1700000000,
            1800000000,
            1700000000,
            1700000100,
            $metadata
        );
    }
}