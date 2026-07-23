<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\CommercePurchaseType;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;

/**
 * Tests for the unified Commerce purchase service.
 *
 * @covers \local_subscriptions\commerce\CommercePurchaseType
 * @covers \local_subscriptions\commerce\CommercePurchaseService
 */
final class commerce_purchase_service_test extends advanced_testcase {

    public function test_purchase_type_is_normalised(): void {
        $this->assertSame(
            CommercePurchaseType::SUBSCRIPTION,
            CommercePurchaseType::normalise(
                ' Subscription '
            )
        );

        $this->assertSame(
            CommercePurchaseType::DIGITAL,
            CommercePurchaseType::normalise(
                'DIGITAL'
            )
        );
    }

    public function test_unknown_purchase_type_is_rejected(): void {
        $this->expectException(
            \InvalidArgumentException::class
        );

        CommercePurchaseType::normalise(
            'unknown'
        );
    }

    public function test_service_delegates_subscription_lookup(): void {
        $purchase =
            $this->create_subscription_purchase();

        $subscriptionrepository =
            $this->createMock(
                SubscriptionPurchaseRepository::class
            );

        $subscriptionrepository
            ->expects($this->once())
            ->method('get_by_subscription_id')
            ->with(123)
            ->willReturn($purchase);

        $digitalrepository =
            $this->createMock(
                DigitalPurchaseRepository::class
            );

        $service = new CommercePurchaseService(
            $subscriptionrepository,
            $digitalrepository
        );

        $result = $service->get_purchase(
            CommercePurchaseType::SUBSCRIPTION,
            123
        );

        $this->assertSame(
            $purchase,
            $result
        );
    }

    public function test_service_delegates_digital_lookup(): void {
        $purchase =
            $this->create_digital_purchase();

        $subscriptionrepository =
            $this->createMock(
                SubscriptionPurchaseRepository::class
            );

        $digitalrepository =
            $this->createMock(
                DigitalPurchaseRepository::class
            );

        $digitalrepository
            ->expects($this->once())
            ->method('get_by_purchase_id')
            ->with(55)
            ->willReturn($purchase);

        $service = new CommercePurchaseService(
            $subscriptionrepository,
            $digitalrepository
        );

        $result = $service->get_purchase(
            CommercePurchaseType::DIGITAL,
            55
        );

        $this->assertSame(
            $purchase,
            $result
        );
    }

    private function create_subscription_purchase(): SubscriptionPurchase {
        return new SubscriptionPurchase(
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
    }

    private function create_digital_purchase(): DigitalPurchase {
        return new DigitalPurchase(
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
    }
}