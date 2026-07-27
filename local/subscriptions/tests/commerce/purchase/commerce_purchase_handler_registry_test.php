<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandler;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerConflictException;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerNotFoundException;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Tests for the Commerce PurchaseHandler registry.
 *
 * @covers \local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry
 */
final class commerce_purchase_handler_registry_test
    extends advanced_testcase {

    public function test_handler_can_be_resolved(): void {
        $handler = $this->create_handler(
            'subscription',
            CommerceItem::TYPE_SUBSCRIPTION
        );

        $registry =
            new CommercePurchaseHandlerRegistry([
                $handler,
            ]);

        $resolved = $registry->resolve(
            $this->create_item(
                CommerceItem::TYPE_SUBSCRIPTION
            )
        );

        $this->assertSame(
            $handler,
            $resolved
        );
    }

    public function test_unknown_item_is_rejected(): void {
        $registry =
            new CommercePurchaseHandlerRegistry();

        $this->expectException(
            CommercePurchaseHandlerNotFoundException::class
        );

        $registry->resolve(
            $this->create_item(
                CommerceItem::TYPE_DIGITAL
            )
        );
    }

    public function test_duplicate_handler_key_is_rejected(): void {
        $registry =
            new CommercePurchaseHandlerRegistry();

        $registry->register(
            $this->create_handler(
                'subscription',
                CommerceItem::TYPE_SUBSCRIPTION
            )
        );

        $this->expectException(
            CommercePurchaseHandlerConflictException::class
        );

        $registry->register(
            $this->create_handler(
                'subscription',
                CommerceItem::TYPE_SUBSCRIPTION
            )
        );
    }

    public function test_multiple_supporting_handlers_are_rejected(): void {
        $registry =
            new CommercePurchaseHandlerRegistry([
                $this->create_handler(
                    'subscription_first',
                    CommerceItem::TYPE_SUBSCRIPTION
                ),
                $this->create_handler(
                    'subscription_second',
                    CommerceItem::TYPE_SUBSCRIPTION
                ),
            ]);

        $this->expectException(
            CommercePurchaseHandlerConflictException::class
        );

        $registry->resolve(
            $this->create_item(
                CommerceItem::TYPE_SUBSCRIPTION
            )
        );
    }

    private function create_item(
        string $type
    ): CommercePurchaseRequestItem {
        return new CommercePurchaseRequestItem(
            new CommerceItem(
                $type,
                $type . ':test',
                'Test item',
                1
            ),
            1,
            1000,
            'EUR'
        );
    }

    private function create_handler(
        string $key,
        string $supportedtype
    ): CommercePurchaseHandler {
        return new class(
            $key,
            $supportedtype
        ) implements CommercePurchaseHandler {

            public function __construct(
                private readonly string $key,
                private readonly string $supportedtype
            ) {
            }

            public function get_key(): string {
                return $this->key;
            }

            public function supports(
                CommercePurchaseRequestItem $item
            ): bool {
                return $item->get_item()->get_type()
                    === $this->supportedtype;
            }

            public function validate(
                CommercePurchaseRequestItem $item,
                CommerceCustomer $customer
            ): CommercePurchaseValidationResult {
                return CommercePurchaseValidationResult::valid();
            }

            public function prepare(
                CommercePurchaseRequestItem $item,
                CommerceCustomer $customer
            ): CommercePreparedPurchaseItem {
                return new CommercePreparedPurchaseItem(
                    $item,
                    $this->key,
                    'test_fulfillment'
                );
            }
        };
    }
}