<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\legacy\DigitalPurchaseFactory;
use local_subscriptions\commerce\legacy\LegacyCommerceStatusMapper;
use local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory;

/**
 * Tests for read-only Commerce adapters over legacy records.
 *
 * @covers \local_subscriptions\commerce\legacy\LegacyCommerceStatusMapper
 * @covers \local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory
 * @covers \local_subscriptions\commerce\legacy\DigitalPurchaseFactory
 */
final class commerce_legacy_factory_test extends advanced_testcase {

    public function test_legacy_statuses_are_normalised(): void {
        $this->assertSame(
            CommercePayment::STATUS_PAID,
            LegacyCommerceStatusMapper::payment_status('paid')
        );

        $this->assertSame(
            CommercePayment::STATUS_COMPLETED,
            LegacyCommerceStatusMapper::payment_status('completed')
        );

        $this->assertSame(
            CommercePayment::STATUS_FAILED,
            LegacyCommerceStatusMapper::payment_status('declined')
        );

        $this->assertSame(
            CommercePayment::STATUS_CANCELLED,
            LegacyCommerceStatusMapper::payment_status('canceled')
        );

        $this->assertSame(
            CommercePayment::STATUS_UNKNOWN,
            LegacyCommerceStatusMapper::payment_status('legacy_custom')
        );
    }

    public function test_subscription_factory_preserves_legacy_data(): void {
        $subscription = (object)[
            'id' => 123,
            'userid' => 96,
            'planid' => 14,
            'pricepaid' => 120.00,
            'currency' => 'EUR',
            'transactionid' => 'legacy-subscription-transaction',
            'payment_provider' => 'stripe',
            'start_date' => 1700000000,
            'end_date' => 1800000000,
            'status' => 'active',
            'creation_date' => 1700000000,
            'last_update' => 1700000100,
            'payment_failed' => 0,
            'discount_percent' => 20,
            'discount_amount' => 30.00,
            'discount_reason' => 'trial72h',
        ];

        $paymentrequest = (object)[
            'id' => 456,
            'subscriptionid' => 123,
            'planid' => 14,
            'userid' => 96,
            'email' => 'student@example.com',
            'currency' => 'EUR',
            'price' => 120.00,
            'amount_minor' => 12000,
            'payment_provider' => 'stripe',
            'status' => 'completed',
            'transactionid' => 'payment-request-transaction',
            'sessionid' => 'checkout-session',
        ];

        $plan = (object)[
            'id' => 14,
            'name' => 'CampusFR A1',
            'access_scope_id' => 4,
        ];

        $user = (object)[
            'id' => 96,
            'email' => 'student@example.com',
        ];

        $purchase = SubscriptionPurchaseFactory::from_legacy_records(
            $subscription,
            $paymentrequest,
            $plan,
            $user
        );

        $this->assertInstanceOf(
            SubscriptionPurchase::class,
            $purchase
        );

        $this->assertSame(
            'subscription:123',
            $purchase->get_reference()
        );

        $this->assertSame(
            123,
            $purchase->get_legacy_subscription_id()
        );

        $this->assertSame(
            456,
            $purchase->get_payment()->get_legacy_request_id()
        );

        $this->assertSame(
            12000,
            $purchase->get_payment()->get_amount_minor()
        );

        $this->assertSame(
            'payment-request-transaction',
            $purchase->get_payment()->get_transaction_id()
        );

        $this->assertSame(
            20,
            $purchase->get_metadata_value('discount_percent')
        );
    }

    public function test_subscription_factory_can_use_subscription_without_request(): void {
        $subscription = (object)[
            'id' => 124,
            'userid' => 96,
            'planid' => 14,
            'pricepaid' => 99.00,
            'currency' => 'EUR',
            'transactionid' => 'historical-transaction',
            'payment_provider' => 'alfa',
            'start_date' => 1700000000,
            'end_date' => 1800000000,
            'status' => 'active',
            'creation_date' => 1700000000,
            'payment_failed' => 0,
        ];

        $purchase = SubscriptionPurchaseFactory::from_legacy_records(
            $subscription
        );

        $this->assertSame(
            9900,
            $purchase->get_payment()->get_amount_minor()
        );

        $this->assertSame(
            'alfa',
            $purchase->get_payment()->get_provider()
        );

        $this->assertTrue(
            $purchase->get_payment()->is_successful()
        );
    }

    public function test_digital_factory_preserves_legacy_data(): void {
        $paymentrequest = (object)[
            'id' => 55,
            'productid' => 8,
            'userid' => 96,
            'email' => 'student@example.com',
            'firstname' => 'Test',
            'lastname' => 'Student',
            'currency' => 'EUR',
            'price' => 19.00,
            'amount_minor' => 1900,
            'payment_provider' => 'stripe',
            'status' => 'completed',
            'transactionid' => 'digital-transaction',
            'sessionid' => 'digital-session',
            'download_token' => 'download-token',
            'download_token_expires' => 1900000000,
            'creation_date' => 1700000000,
            'last_update' => 1700000100,
            'emailsent' => 1,
            'receipt_sent' => 1,
            'attempts' => 1,
            'locked_list_price' => 29.00,
            'locked_discount_percent' => 10,
            'locked_discount_amount' => 2.90,
            'locked_discount_reason' => 'bundle',
            'locked_final_price' => 19.00,
        ];

        $product = (object)[
            'id' => 8,
            'name' => 'PDF des verbes',
            'slug' => 'verbs-pdf',
            'enabled' => 1,
        ];

        $purchase = DigitalPurchaseFactory::from_legacy_records(
            $paymentrequest,
            $product
        );

        $this->assertInstanceOf(
            DigitalPurchase::class,
            $purchase
        );

        $this->assertSame(
            'digital:55',
            $purchase->get_reference()
        );

        $this->assertSame(
            'digital-product:verbs-pdf',
            $purchase->get_item()->get_reference()
        );

        $this->assertSame(
            1900,
            $purchase->get_payment()->get_amount_minor()
        );

        $this->assertSame(
            'download-token',
            $purchase->get_download_token()
        );

        $this->assertSame(
            10,
            $purchase->get_metadata_value('locked_discount_percent')
        );
    }

    public function test_subscription_without_identifier_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        SubscriptionPurchaseFactory::from_legacy_records(
            (object)[
                'planid' => 14,
            ]
        );
    }

    public function test_digital_purchase_without_product_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        DigitalPurchaseFactory::from_legacy_records(
            (object)[
                'id' => 55,
            ]
        );
    }
}