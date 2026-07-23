<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchasePreparationException;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPlanDescriptor;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPlanRepository;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPurchaseHandler;

/**
 * Tests for the subscription PurchaseHandler.
 *
 * @covers \local_subscriptions\commerce\purchase\subscription\SubscriptionPlanDescriptor
 * @covers \local_subscriptions\commerce\purchase\subscription\SubscriptionPurchaseHandler
 */
final class subscription_purchase_handler_test
    extends advanced_testcase {

    public function test_subscription_item_is_supported(): void {
        $handler =
            new SubscriptionPurchaseHandler(
                $this->create_repository()
            );

        $this->assertTrue(
            $handler->supports(
                $this->create_item()
            )
        );
    }

    public function test_active_plan_can_be_prepared(): void {
        $handler =
            new SubscriptionPurchaseHandler(
                $this->create_repository()
            );

        $customer = new CommerceCustomer(
            96,
            'student@example.com'
        );

        $prepared = $handler->prepare(
            $this->create_item(),
            $customer
        );

        $this->assertSame(
            SubscriptionPurchaseHandler::KEY,
            $prepared->get_handler_key()
        );

        $this->assertSame(
            SubscriptionPurchaseHandler::FULFILLMENT_KEY,
            $prepared->get_fulfillment_key()
        );

        $this->assertSame(
            14,
            $prepared
                ->get_fulfillment_metadata()['planid']
        );

        $this->assertSame(
            96,
            $prepared
                ->get_fulfillment_metadata()['userid']
        );

        $this->assertSame(
            'CampusFR A1',
            $prepared
                ->get_payment_metadata()['description']
        );
    }

    public function test_inactive_plan_is_rejected(): void {
        $repository =
            new class implements SubscriptionPlanRepository {

                public function find(
                    int $planid
                ): ?SubscriptionPlanDescriptor {
                    return new SubscriptionPlanDescriptor(
                        $planid,
                        'Inactive plan',
                        false,
                        false,
                        false,
                        null,
                        '1month'
                    );
                }
            };

        $handler =
            new SubscriptionPurchaseHandler(
                $repository
            );

        $validation = $handler->validate(
            $this->create_item(),
            new CommerceCustomer(
                96,
                'student@example.com'
            )
        );

        $this->assertFalse(
            $validation->is_valid()
        );

        $this->assertSame(
            'subscription_plan_inactive',
            $validation
                ->get_errors()[0]
                ->get_code()
        );
    }

    public function test_multiple_subscription_quantity_is_rejected():
        void {
        $handler =
            new SubscriptionPurchaseHandler(
                $this->create_repository()
            );

        $item = new CommercePurchaseRequestItem(
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'subscription-plan:14',
                'CampusFR A1',
                14
            ),
            2,
            12000,
            'EUR'
        );

        $this->expectException(
            CommercePurchasePreparationException::class
        );

        $handler->prepare(
            $item,
            new CommerceCustomer(
                96,
                'student@example.com'
            )
        );
    }

    public function test_trial_plan_with_non_zero_amount_adds_warning():
        void {
        $repository =
            new class implements SubscriptionPlanRepository {

                public function find(
                    int $planid
                ): ?SubscriptionPlanDescriptor {
                    return new SubscriptionPlanDescriptor(
                        $planid,
                        'Trial plan',
                        true,
                        true,
                        false,
                        4,
                        '7days'
                    );
                }
            };

        $handler =
            new SubscriptionPurchaseHandler(
                $repository
            );

        $validation = $handler->validate(
            $this->create_item(),
            new CommerceCustomer(
                96,
                'student@example.com'
            )
        );

        $this->assertTrue(
            $validation->is_valid()
        );

        $this->assertTrue(
            $validation->has_warnings()
        );

        $this->assertSame(
            'paid_trial_subscription',
            $validation
                ->get_warnings()[0]
                ->get_code()
        );
    }

    private function create_item():
        CommercePurchaseRequestItem {
        return new CommercePurchaseRequestItem(
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
    }

    private function create_repository():
        SubscriptionPlanRepository {
        return new class implements SubscriptionPlanRepository {

            public function find(
                int $planid
            ): ?SubscriptionPlanDescriptor {
                if ($planid !== 14) {
                    return null;
                }

                return new SubscriptionPlanDescriptor(
                    14,
                    'CampusFR A1',
                    true,
                    false,
                    false,
                    4,
                    '1year'
                );
            }
        };
    }
}