<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus;

/**
 * Tests for Commerce purchase requests.
 *
 * @covers \local_subscriptions\commerce\purchase\CommerceCustomer
 * @covers \local_subscriptions\commerce\purchase\CommercePurchaseRequest
 * @covers \local_subscriptions\commerce\purchase\CommercePurchaseRequestItem
 * @covers \local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus
 */
final class commerce_purchase_request_test
    extends advanced_testcase {

    public function test_authenticated_customer_can_be_created(): void {
        $customer = new CommerceCustomer(
            96,
            'Student@Example.com',
            'Test',
            'Student'
        );

        $this->assertSame(
            96,
            $customer->get_user_id()
        );

        $this->assertSame(
            'student@example.com',
            $customer->get_email()
        );

        $this->assertSame(
            'Test Student',
            $customer->get_full_name()
        );

        $this->assertTrue(
            $customer->is_authenticated()
        );
    }

    public function test_guest_customer_can_be_created(): void {
        $customer = new CommerceCustomer(
            null,
            'guest@example.com'
        );

        $this->assertTrue(
            $customer->is_guest()
        );
    }

    public function test_invalid_email_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        new CommerceCustomer(
            null,
            'invalid-email'
        );
    }

    public function test_single_item_request_calculates_total(): void {
        $request = new CommercePurchaseRequest(
            'purchase-request:test-1',
            new CommerceCustomer(
                96,
                'student@example.com'
            ),
            [
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
                ),
            ],
            CommercePurchaseRequestStatus::DRAFT,
            'stripe'
        );

        $this->assertSame(
            12000,
            $request->get_total_amount_minor()
        );

        $this->assertSame(
            'EUR',
            $request->get_currency()
        );

        $this->assertSame(
            'stripe',
            $request->get_preferred_provider()
        );

        $this->assertFalse(
            $request->is_free()
        );
    }

    public function test_bundle_request_can_contain_multiple_items(): void {
        $request = new CommercePurchaseRequest(
            'purchase-request:bundle-1',
            new CommerceCustomer(
                96,
                'student@example.com'
            ),
            [
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
                ),

                new CommercePurchaseRequestItem(
                    new CommerceItem(
                        CommerceItem::TYPE_DIGITAL,
                        'digital-product:8',
                        'PDF des verbes',
                        8
                    ),
                    1,
                    1900,
                    'EUR'
                ),
            ]
        );

        $this->assertTrue(
            $request->contains_multiple_items()
        );

        $this->assertSame(
            13900,
            $request->get_total_amount_minor()
        );
    }

    public function test_multiple_quantities_are_supported(): void {
        $item = new CommercePurchaseRequestItem(
            new CommerceItem(
                CommerceItem::TYPE_DIGITAL,
                'digital-product:8',
                'PDF des verbes',
                8
            ),
            3,
            1900,
            'EUR'
        );

        $this->assertSame(
            5700,
            $item->get_total_amount_minor()
        );
    }

    public function test_mixed_currencies_are_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        new CommercePurchaseRequest(
            'purchase-request:mixed-currencies',
            new CommerceCustomer(
                96,
                'student@example.com'
            ),
            [
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
                ),

                new CommercePurchaseRequestItem(
                    new CommerceItem(
                        CommerceItem::TYPE_DIGITAL,
                        'digital-product:8',
                        'PDF des verbes',
                        8
                    ),
                    1,
                    150000,
                    'RUB'
                ),
            ]
        );
    }

    public function test_request_status_can_change_immutably(): void {
        $request = $this->create_request();

        $validated = $request->with_status(
            CommercePurchaseRequestStatus::VALIDATED
        );

        $this->assertSame(
            CommercePurchaseRequestStatus::DRAFT,
            $request->get_status()
        );

        $this->assertSame(
            CommercePurchaseRequestStatus::VALIDATED,
            $validated->get_status()
        );

        $this->assertNotSame(
            $request,
            $validated
        );
    }

    private function create_request():
        CommercePurchaseRequest {
        return new CommercePurchaseRequest(
            'purchase-request:test',
            new CommerceCustomer(
                96,
                'student@example.com'
            ),
            [
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
                ),
            ]
        );
    }
}