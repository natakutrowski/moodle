<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentExecutionException;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Tests the Commerce fulfillment architecture.
 *
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentBatchResult
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperationValidator
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandlerRegistry
 * @covers \local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator
 */
final class commerce_fulfillment_architecture_test
    extends advanced_testcase {

    public function test_preparation_can_be_converted_to_operations(): void {
        $coordinator = new CommerceFulfillmentCoordinator(
            new CommerceFulfillmentHandlerRegistry()
        );

        $operations = $coordinator->plan(
            $this->create_preparation()
        );

        $this->assertCount(1, $operations);
        $this->assertSame(
            'subscription_enrolment',
            $operations[0]->get_key()
        );
        $this->assertSame(
            14,
            $operations[0]->get_metadata_value('planid')
        );
        $this->assertSame(
            'purchase-request:test:item:1:subscription_enrolment',
            $operations[0]->get_idempotency_key()
        );
    }

    public function test_registered_handler_can_execute_operation(): void {
        $handler = new class implements CommerceFulfillmentHandler {
            public function get_key(): string {
                return 'subscription_enrolment';
            }

            public function supports(
                CommerceFulfillmentOperation $operation
            ): bool {
                return $operation->get_key() === $this->get_key();
            }

            public function fulfill(
                CommerceFulfillmentOperation $operation,
                CommerceFulfillmentContext $context
            ): CommerceFulfillmentResult {
                return new CommerceFulfillmentResult(
                    $operation,
                    CommerceFulfillmentResult::STATUS_COMPLETED
                );
            }
        };

        $coordinator = new CommerceFulfillmentCoordinator(
            new CommerceFulfillmentHandlerRegistry([$handler])
        );

        $batch = $coordinator->fulfill(
            $coordinator->plan($this->create_preparation()),
            $this->create_context()
        );

        $this->assertTrue($batch->is_successful());
        $this->assertSame(1, $batch->count_completed());
        $this->assertSame(0, $batch->count_failed());
    }

    public function test_unconfirmed_payment_is_rejected(): void {
        $coordinator = new CommerceFulfillmentCoordinator(
            new CommerceFulfillmentHandlerRegistry()
        );

        $context = new CommerceFulfillmentContext(
            'purchase-request:test',
            false,
            'stripe',
            'pi_test',
            12000,
            'EUR',
            time()
        );

        $this->expectException(
            CommerceFulfillmentExecutionException::class
        );

        $coordinator->fulfill(
            $coordinator->plan($this->create_preparation()),
            $context
        );
    }

    private function create_context(): CommerceFulfillmentContext {
        return CommerceFulfillmentContext::confirmed(
            'purchase-request:test',
            'stripe',
            'pi_test',
            12000,
            'EUR',
            time(),
            91,
            'phpunit'
        );
    }

    private function create_preparation(): CommercePurchasePreparation {
        $requestitem = new CommercePurchaseRequestItem(
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

        $request = new CommercePurchaseRequest(
            'purchase-request:test',
            new CommerceCustomer(96, 'student@example.com'),
            [$requestitem]
        );

        $prepareditem = new CommercePreparedPurchaseItem(
            $requestitem,
            'subscription',
            'subscription_enrolment',
            ['description' => 'CampusFR A1'],
            [
                'planid' => 14,
                'userid' => 96,
                'duration_key' => '1year',
            ]
        );

        return new CommercePurchasePreparation(
            $request,
            [$prepareditem],
            CommercePurchaseValidationResult::valid(),
            time()
        );
    }
}
