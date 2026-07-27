<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\crm\commerce\CrmCommerceCustomerService;

/**
 * Tests for the CRM Commerce customer bridge.
 *
 * @covers \local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot
 * @covers \local_subscriptions\crm\commerce\CrmCommerceCustomerService
 */
final class crm_commerce_customer_service_test extends advanced_testcase {

    public function test_snapshot_aggregates_all_purchase_types(): void {
        $subscription = new SubscriptionPurchase(
            'subscription:123',
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'subscription-plan:14',
                'CampusFR A1',
                14
            ),
            new CommercePayment(
                12000,
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
            1700000000
        );

        $digital = new DigitalPurchase(
            'digital:55',
            new CommerceItem(
                CommerceItem::TYPE_DIGITAL,
                'digital-product:verbs-pdf',
                'PDF des verbes',
                8
            ),
            new CommercePayment(
                1900,
                'EUR',
                CommercePayment::STATUS_COMPLETED,
                'paypal'
            ),
            96,
            'student@example.com',
            'completed',
            55,
            8,
            'download-token',
            1900000000,
            1700000100
        );

        $purchaseservice = $this->getMockBuilder(
            CommercePurchaseService::class
        )
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get_customer_purchases',
            ])
            ->getMock();

        $purchaseservice
            ->expects($this->once())
            ->method('get_customer_purchases')
            ->with(
                96,
                'student@example.com'
            )
            ->willReturn([
                $digital,
                $subscription,
            ]);

        $service = new CrmCommerceCustomerService(
            $purchaseservice
        );

        $snapshot = $service->build_snapshot(
            96,
            'student@example.com'
        );

        $this->assertSame(
            2,
            $snapshot->get_purchase_count()
        );

        $this->assertSame(
            1,
            $snapshot->get_subscription_count()
        );

        $this->assertSame(
            1,
            $snapshot->get_digital_purchase_count()
        );

        $this->assertSame(
            [
                'EUR' => 13900,
            ],
            $snapshot->get_revenue_by_currency()
        );

        $this->assertSame(
            [
                'paypal' => 1,
                'stripe' => 1,
            ],
            $snapshot->get_provider_usage()
        );

        $this->assertTrue(
            $snapshot->has_used_provider(
                'paypal'
            )
        );

        $this->assertSame(
            [
                'completed' => 1,
                'fulfilled' => 1,
            ],
            $snapshot->get_status_usage()
        );

        $this->assertSame(
            1700000000,
            $snapshot->get_first_purchase_at()
        );

        $this->assertSame(
            1700000100,
            $snapshot->get_last_purchase_at()
        );
    }

    public function test_snapshot_uses_canonical_lifecycle_statuses(): void {
        $paymentpending = new DigitalPurchase(
            'digital:56',
            new CommerceItem(
                CommerceItem::TYPE_DIGITAL,
                'digital-product:pending',
                'Pending product',
                9
            ),
            new CommercePayment(
                1900,
                'EUR',
                CommercePayment::STATUS_PENDING,
                'stripe'
            ),
            96,
            'student@example.com',
            'pending',
            56,
            9
        );

        $captured = new SubscriptionPurchase(
            'subscription:124',
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'subscription-plan:15',
                'CampusFR A2',
                15
            ),
            new CommercePayment(
                12000,
                'EUR',
                CommercePayment::STATUS_COMPLETED,
                'stripe'
            ),
            96,
            'student@example.com',
            'suspended',
            124,
            15
        );

        $purchaseservice = $this->getMockBuilder(
            CommercePurchaseService::class
        )
            ->disableOriginalConstructor()
            ->onlyMethods([
                'get_customer_purchases',
            ])
            ->getMock();

        $purchaseservice
            ->expects($this->once())
            ->method('get_customer_purchases')
            ->with(
                96,
                'student@example.com'
            )
            ->willReturn([
                $paymentpending,
                $captured,
            ]);

        $snapshot = (new CrmCommerceCustomerService(
            $purchaseservice
        ))->build_snapshot(
            96,
            'student@example.com'
        );

        $this->assertSame(
            [
                'captured' => 1,
                'payment_pending' => 1,
            ],
            $snapshot->get_status_usage()
        );
    }

}