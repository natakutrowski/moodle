<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\digital\DigitalProductDescriptor;
use local_subscriptions\commerce\purchase\digital\DigitalProductRepository;
use local_subscriptions\commerce\purchase\digital\DigitalPurchaseHandler;
use local_subscriptions\commerce\purchase\handler\CommercePurchasePreparationException;

/**
 * Tests for the digital PurchaseHandler.
 *
 * @covers \local_subscriptions\commerce\purchase\digital\DigitalProductDescriptor
 * @covers \local_subscriptions\commerce\purchase\digital\DigitalPurchaseHandler
 */
final class digital_purchase_handler_test
    extends advanced_testcase {

    public function test_digital_item_is_supported(): void {
        $handler = new DigitalPurchaseHandler(
            $this->create_repository()
        );

        $this->assertTrue(
            $handler->supports(
                $this->create_item()
            )
        );
    }

    public function test_active_product_can_be_prepared(): void {
        $handler = new DigitalPurchaseHandler(
            $this->create_repository()
        );

        $prepared = $handler->prepare(
            $this->create_item(),
            new CommerceCustomer(
                96,
                'student@example.com'
            )
        );

        $this->assertSame(
            DigitalPurchaseHandler::KEY,
            $prepared->get_handler_key()
        );

        $this->assertSame(
            DigitalPurchaseHandler::FULFILLMENT_KEY,
            $prepared->get_fulfillment_key()
        );

        $this->assertSame(
            8,
            $prepared
                ->get_fulfillment_metadata()['productid']
        );

        $this->assertSame(
            'verbs.pdf',
            $prepared
                ->get_fulfillment_metadata()['filename']
        );
    }

    public function test_inactive_product_is_rejected(): void {
        $repository =
            new class implements DigitalProductRepository {

                public function find(
                    int $productid
                ): ?DigitalProductDescriptor {
                    return new DigitalProductDescriptor(
                        $productid,
                        'Inactive PDF',
                        'inactive-pdf',
                        false,
                        'inactive.pdf'
                    );
                }
            };

        $handler =
            new DigitalPurchaseHandler(
                $repository
            );

        $this->expectException(
            CommercePurchasePreparationException::class
        );

        $handler->prepare(
            $this->create_item(),
            new CommerceCustomer(
                96,
                'student@example.com'
            )
        );
    }

    public function test_missing_file_adds_warning(): void {
        $repository =
            new class implements DigitalProductRepository {

                public function find(
                    int $productid
                ): ?DigitalProductDescriptor {
                    return new DigitalProductDescriptor(
                        $productid,
                        'PDF without file',
                        'pdf-without-file',
                        true,
                        null
                    );
                }
            };

        $handler =
            new DigitalPurchaseHandler(
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
            'digital_product_file_missing',
            $validation
                ->get_warnings()[0]
                ->get_code()
        );
    }

    private function create_item():
        CommercePurchaseRequestItem {
        return new CommercePurchaseRequestItem(
            new CommerceItem(
                CommerceItem::TYPE_DIGITAL,
                'digital-product:8',
                'PDF des verbes',
                8
            ),
            1,
            1900,
            'EUR'
        );
    }

    private function create_repository():
        DigitalProductRepository {
        return new class implements DigitalProductRepository {

            public function find(
                int $productid
            ): ?DigitalProductDescriptor {
                if ($productid !== 8) {
                    return null;
                }

                return new DigitalProductDescriptor(
                    8,
                    'PDF des verbes',
                    'pdf-verbes',
                    true,
                    'verbs.pdf'
                );
            }
        };
    }
}