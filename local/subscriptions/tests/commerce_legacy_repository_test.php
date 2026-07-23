<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;

/**
 * Tests for read-only Commerce repositories over historical tables.
 *
 * @covers \local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository
 * @covers \local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository
 */
final class commerce_legacy_repository_test extends advanced_testcase {

    public function test_subscription_repository_reads_existing_tables(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $scopeid = $DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Commerce test scope',
                'course_ids' => '[]',
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $planid = $DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Commerce test plan',
                'access_scope_id' => $scopeid,
                'duration_key' => '1month',
                'is_active' => 1,
                'creation_date' => time(),
                'last_update' => time(),
                'is_recurring' => 0,
                'is_trial' => 0,
                'expiry_reminder_enabled' => 1,
            ]
        );

        $subscriptionid = $DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $planid,
                'pricepaid' => 49.00,
                'currency' => 'EUR',
                'transactionid' => 'test-subscription-transaction',
                'payment_provider' => 'stripe',
                'start_date' => time(),
                'end_date' => time() + 30*DAYSECS,
                'status' => 'active',
                'last_update' => time(),
                'creation_date' => time(),
                'payment_failed' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
            ]
        );

        $repository =
            new SubscriptionPurchaseRepository();

        $purchase =
            $repository->get_by_subscription_id(
                $subscriptionid
            );

        $this->assertInstanceOf(
            SubscriptionPurchase::class,
            $purchase
        );

        $this->assertSame(
            $subscriptionid,
            $purchase->get_legacy_subscription_id()
        );

        $this->assertSame(
            $planid,
            $purchase->get_plan_id()
        );

        $this->assertSame(
            (int)$user->id,
            $purchase->get_user_id()
        );
    }

    public function test_subscription_repository_returns_null_when_missing(): void {
        $this->resetAfterTest();

        $repository =
            new SubscriptionPurchaseRepository();

        $this->assertNull(
            $repository->get_by_subscription_id(
                999999999
            )
        );
    }

    public function test_digital_repository_reads_existing_tables(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $productid = $DB->insert_record(
            'subscription_digital_product',
            (object)[
                'name' => 'Commerce test PDF',
                'slug' => 'commerce-test-pdf',
                'enabled' => 1,
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $purchaseid = $DB->insert_record(
            'subscription_digital_payment_request',
            (object)[
                'productid' => $productid,
                'userid' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'currency' => 'EUR',
                'price' => 19.00,
                'amount_minor' => 1900,
                'payment_provider' => 'stripe',
                'status' => 'completed',
                'transactionid' => 'test-digital-transaction',
                'creation_date' => time(),
                'last_update' => time(),
                'emailsent' => 0,
                'receipt_sent' => 0,
                'attempts' => 0,
            ]
        );

        $repository =
            new DigitalPurchaseRepository();

        $purchase =
            $repository->get_by_purchase_id(
                $purchaseid
            );

        $this->assertInstanceOf(
            DigitalPurchase::class,
            $purchase
        );

        $this->assertSame(
            $purchaseid,
            $purchase->get_legacy_purchase_id()
        );

        $this->assertSame(
            $productid,
            $purchase->get_product_id()
        );
    }
}