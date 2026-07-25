<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;

/**
 * Tests for immutable Commerce purchase lines.
 *
 * @covers \local_subscriptions\commerce\domain\value\CommercePurchaseItem
 */
final class commerce_purchase_item_test extends advanced_testcase {

    public function test_purchase_item_locks_financial_snapshot(): void {
        $item = $this->create_catalog_item();
        $purchaseitem = new CommercePurchaseItem(
            $item,
            2,
            CommerceMoney::from_minor(10000, 'eur'),
            CommerceMoney::from_minor(1500, 'EUR'),
            [
                'rule' => 'bundle_discount',
            ],
            [
                'courseids' => [17],
            ],
            [
                'source' => 'checkout',
            ]
        );

        $this->assertSame($item, $purchaseitem->get_item());
        $this->assertSame(2, $purchaseitem->get_quantity());
        $this->assertSame('EUR', $purchaseitem->get_currency());
        $this->assertSame(20000, $purchaseitem->get_gross_amount()->get_amount_minor());
        $this->assertSame(1500, $purchaseitem->get_discount()->get_amount_minor());
        $this->assertSame(18500, $purchaseitem->get_net_amount()->get_amount_minor());
        $this->assertSame(
            'bundle_discount',
            $purchaseitem->get_pricing_snapshot_value('rule')
        );
        $this->assertSame(
            [17],
            $purchaseitem->get_fulfillment_snapshot_value('courseids')
        );
        $this->assertSame(
            'checkout',
            $purchaseitem->get_metadata_value('source')
        );
        $this->assertTrue($purchaseitem->has_discount());
        $this->assertFalse($purchaseitem->is_free());
    }

    public function test_missing_discount_defaults_to_zero(): void {
        $purchaseitem = new CommercePurchaseItem(
            $this->create_catalog_item(),
            1,
            CommerceMoney::from_minor(1900, 'EUR')
        );

        $this->assertTrue($purchaseitem->get_discount()->is_zero());
        $this->assertSame(1900, $purchaseitem->get_net_amount()->get_amount_minor());
        $this->assertFalse($purchaseitem->has_discount());
    }

    public function test_full_discount_creates_free_item(): void {
        $purchaseitem = new CommercePurchaseItem(
            $this->create_catalog_item(),
            1,
            CommerceMoney::from_minor(1900, 'EUR'),
            CommerceMoney::from_minor(1900, 'EUR')
        );

        $this->assertTrue($purchaseitem->is_free());
        $this->assertSame(0, $purchaseitem->get_net_amount()->get_amount_minor());
    }

    public function test_non_positive_quantity_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseItem(
            $this->create_catalog_item(),
            0,
            CommerceMoney::from_minor(1900, 'EUR')
        );
    }

    public function test_discount_currency_must_match_unit_price(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseItem(
            $this->create_catalog_item(),
            1,
            CommerceMoney::from_minor(1900, 'EUR'),
            CommerceMoney::from_minor(100, 'RUB')
        );
    }

    public function test_discount_cannot_exceed_gross_amount(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseItem(
            $this->create_catalog_item(),
            2,
            CommerceMoney::from_minor(1000, 'EUR'),
            CommerceMoney::from_minor(2001, 'EUR')
        );
    }

    public function test_serialised_snapshot_contains_locked_amounts(): void {
        $purchaseitem = new CommercePurchaseItem(
            $this->create_catalog_item(),
            3,
            CommerceMoney::from_minor(1000, 'EUR'),
            CommerceMoney::from_minor(500, 'EUR')
        );

        $snapshot = $purchaseitem->to_array();

        $this->assertSame('digital', $snapshot['itemtype']);
        $this->assertSame('digital-product:8', $snapshot['itemreference']);
        $this->assertSame(3, $snapshot['quantity']);
        $this->assertSame(3000, $snapshot['grossamount']['amountminor']);
        $this->assertSame(2500, $snapshot['netamount']['amountminor']);
    }

    private function create_catalog_item(): CommerceItem {
        return new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:8',
            'PDF des verbes',
            8
        );
    }
}
