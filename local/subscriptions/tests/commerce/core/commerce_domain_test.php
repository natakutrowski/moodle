<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommerceOrder;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Tests for the generic Commerce domain objects.
 *
 * @covers \local_subscriptions\commerce\domain\CommerceItem
 * @covers \local_subscriptions\commerce\domain\CommerceOrder
 * @covers \local_subscriptions\commerce\domain\CommercePayment
 * @covers \local_subscriptions\commerce\domain\CommercePurchase
 * @covers \local_subscriptions\commerce\domain\purchase\SubscriptionPurchase
 * @covers \local_subscriptions\commerce\domain\purchase\DigitalPurchase
 */
final class commerce_domain_test extends advanced_testcase {

    public function test_subscription_item_can_be_created(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_SUBSCRIPTION,
            'subscription-plan:14',
            'CampusFR A1',
            14,
            [
                'access_scope_id' => 4,
            ]
        );

        $this->assertSame(
            CommerceItem::TYPE_SUBSCRIPTION,
            $item->get_type()
        );

        $this->assertSame(
            'subscription-plan:14',
            $item->get_reference()
        );

        $this->assertSame(
            14,
            $item->get_legacy_id()
        );

        $this->assertSame(
            4,
            $item->get_metadata_value('access_scope_id')
        );

        $this->assertTrue(
            $item->is_subscription()
        );

        $this->assertFalse(
            $item->is_digital()
        );
    }

    public function test_digital_item_can_be_created(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:verbs-pdf',
            'PDF des verbes',
            8
        );

        $this->assertSame(
            CommerceItem::TYPE_DIGITAL,
            $item->get_type()
        );

        $this->assertSame(
            8,
            $item->get_legacy_id()
        );

        $this->assertTrue(
            $item->is_digital()
        );
    }

    public function test_invalid_item_type_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommerceItem(
            'unsupported',
            'unsupported:1',
            'Unsupported item'
        );
    }

    public function test_payment_from_major_amount_uses_minor_units(): void {
        $payment = CommercePayment::from_major_amount(
            149.90,
            'eur',
            CommercePayment::STATUS_PAID,
            'stripe',
            'pi_test_123',
            42,
            1700000000
        );

        $this->assertSame(
            14990,
            $payment->get_amount_minor()
        );

        $this->assertSame(
            149.90,
            $payment->get_amount_major()
        );

        $this->assertSame(
            'EUR',
            $payment->get_currency()
        );

        $this->assertSame(
            'stripe',
            $payment->get_provider()
        );

        $this->assertTrue(
            $payment->is_successful()
        );
    }

    public function test_paypal_is_accepted_as_provider_identifier(): void {
        $payment = new CommercePayment(
            9900,
            'EUR',
            CommercePayment::STATUS_PENDING,
            'paypal'
        );

        $this->assertSame(
            'paypal',
            $payment->get_provider()
        );

        $this->assertTrue(
            $payment->is_pending()
        );
    }

    public function test_negative_payment_amount_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommercePayment(
            -1,
            'EUR',
            CommercePayment::STATUS_PENDING
        );
    }

    public function test_invalid_currency_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommercePayment(
            1000,
            'EURO',
            CommercePayment::STATUS_PENDING
        );
    }

    public function test_subscription_purchase_wraps_legacy_identity(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_SUBSCRIPTION,
            'subscription-plan:14',
            'CampusFR A1',
            14
        );

        $payment = new CommercePayment(
            12000,
            'EUR',
            CommercePayment::STATUS_COMPLETED,
            'stripe',
            'pi_test_subscription'
        );

        $purchase = new SubscriptionPurchase(
            'subscription:123',
            $item,
            $payment,
            96,
            'student@example.com',
            'active',
            123,
            14,
            1700000000,
            1800000000,
            1700000000,
            1700000100
        );

        $this->assertSame(
            'subscription',
            $purchase->get_type()
        );

        $this->assertSame(
            123,
            $purchase->get_legacy_subscription_id()
        );

        $this->assertSame(
            14,
            $purchase->get_plan_id()
        );

        $this->assertTrue(
            $purchase->is_paid()
        );

        $this->assertTrue(
            $purchase->is_current_at(1750000000)
        );
    }

    public function test_digital_purchase_wraps_legacy_identity(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:verbs-pdf',
            'PDF des verbes',
            8
        );

        $payment = new CommercePayment(
            1900,
            'EUR',
            CommercePayment::STATUS_COMPLETED,
            'alfa',
            'alfa_test_digital'
        );

        $purchase = new DigitalPurchase(
            'digital:55',
            $item,
            $payment,
            null,
            'guest@example.com',
            'completed',
            55,
            8,
            'download-token',
            1900000000,
            1700000000,
            1700000100
        );

        $this->assertSame(
            'digital',
            $purchase->get_type()
        );

        $this->assertSame(
            55,
            $purchase->get_legacy_purchase_id()
        );

        $this->assertSame(
            8,
            $purchase->get_product_id()
        );

        $this->assertTrue(
            $purchase->has_download_access(1800000000)
        );
    }

    public function test_order_can_contain_multiple_item_types(): void {
        $subscriptionitem = new CommerceItem(
            CommerceItem::TYPE_SUBSCRIPTION,
            'subscription-plan:14',
            'CampusFR A1',
            14
        );

        $digitalitem = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:verbs-pdf',
            'PDF des verbes',
            8
        );

        $payment = new CommercePayment(
            14900,
            'EUR',
            CommercePayment::STATUS_COMPLETED,
            'stripe'
        );

        $order = new CommerceOrder(
            'order:test-1',
            96,
            'student@example.com',
            [
                $subscriptionitem,
                $digitalitem,
            ],
            [
                $payment,
            ],
            CommerceOrder::STATUS_COMPLETED,
            1700000000
        );

        $this->assertCount(
            2,
            $order->get_items()
        );

        $this->assertTrue(
            $order->contains_multiple_items()
        );

        $this->assertTrue(
            $order->is_paid()
        );
    }
}