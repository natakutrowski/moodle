<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\digital\DigitalProductDescriptor;
use local_subscriptions\commerce\purchase\digital\DigitalProductRepository;
use local_subscriptions\commerce\purchase\digital\DigitalPurchaseHandler;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator;
use local_subscriptions\commerce\purchase\preparation\CommercePurchaseRequestValidationException;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPlanDescriptor;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPlanRepository;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPurchaseHandler;

/**
 * Tests the purchase preparation orchestrator.
 *
 * @covers \local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation
 * @covers \local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator
 */
final class commerce_purchase_preparation_orchestrator_test
    extends advanced_testcase {

    public function test_mixed_bundle_can_be_prepared(): void {
        $orchestrator =
            $this->create_orchestrator();

        $request = new CommercePurchaseRequest(
            'purchase-request:bundle',
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

        $preparation =
            $orchestrator->prepare(
                $request
            );

        $this->assertSame(
            13900,
            $preparation->get_total_amount_minor()
        );

        $this->assertSame(
            'EUR',
            $preparation->get_currency()
        );

        $this->assertTrue(
            $preparation->contains_multiple_items()
        );

        $this->assertCount(
            2,
            $preparation->get_payment_lines()
        );

        $this->assertCount(
            2,
            $preparation
                ->get_fulfillment_operations()
        );
    }

    public function test_unknown_item_type_is_rejected(): void {
        $this->expectException(
            \coding_exception::class
        );

        $this->expectExceptionMessage(
            'Unsupported Commerce item type: unknown'
        );

        new CommerceItem(
            'unknown',
            'unknown:1',
            'Unknown product',
            1
        );
    }

    private function create_orchestrator():
        CommercePurchasePreparationOrchestrator {
        $subscriptionrepository =
            new class implements SubscriptionPlanRepository {

                public function find(
                    int $planid
                ): ?SubscriptionPlanDescriptor {
                    return new SubscriptionPlanDescriptor(
                        $planid,
                        'CampusFR A1',
                        true,
                        false,
                        false,
                        4,
                        '1year'
                    );
                }
            };

        $digitalrepository =
            new class implements DigitalProductRepository {

                public function find(
                    int $productid
                ): ?DigitalProductDescriptor {
                    return new DigitalProductDescriptor(
                        $productid,
                        'PDF des verbes',
                        'pdf-verbes',
                        true,
                        'verbs.pdf'
                    );
                }
            };

        $registry =
            new CommercePurchaseHandlerRegistry([
                new SubscriptionPurchaseHandler(
                    $subscriptionrepository
                ),

                new DigitalPurchaseHandler(
                    $digitalrepository
                ),
            ]);

        return new CommercePurchasePreparationOrchestrator(
            $registry
        );
    }

    public function test_item_type_without_registered_handler_is_rejected(): void {
        $orchestrator =
            $this->create_orchestrator();

        $request = new CommercePurchaseRequest(
            'purchase-request:service',
            new CommerceCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePurchaseRequestItem(
                    new CommerceItem(
                        CommerceItem::TYPE_SERVICE,
                        'service:1',
                        'Private lesson',
                        1
                    ),
                    1,
                    5000,
                    'EUR'
                ),
            ]
        );

        $this->expectException(
            CommercePurchaseRequestValidationException::class
        );

        $orchestrator->prepare(
            $request
        );
    }

}