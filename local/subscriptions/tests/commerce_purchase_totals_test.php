<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseTotals;

/**
 * Tests for immutable Commerce purchase totals.
 *
 * @covers \local_subscriptions\commerce\domain\value\CommercePurchaseTotals
 */
final class commerce_purchase_totals_test extends advanced_testcase {

    public function test_totals_are_calculated_from_multiple_items(): void {
        $items = [
            $this->create_purchase_item(
                'subscription-plan:14',
                2,
                10000,
                1500
            ),
            $this->create_purchase_item(
                'digital-product:8',
                1,
                2500,
                500
            ),
        ];

        $totals = CommercePurchaseTotals::from_items($items);

        $this->assertSame('EUR', $totals->get_currency());
        $this->assertSame(22500, $totals->get_subtotal()->get_amount_minor());
        $this->assertSame(2000, $totals->get_discount()->get_amount_minor());
        $this->assertSame(20500, $totals->get_total()->get_amount_minor());
        $this->assertTrue($totals->has_discount());
        $this->assertFalse($totals->is_free());
    }

    public function test_free_purchase_totals_are_supported(): void {
        $totals = CommercePurchaseTotals::from_items([
            $this->create_purchase_item(
                'digital-product:8',
                1,
                1900,
                1900
            ),
        ]);

        $this->assertTrue($totals->is_free());
        $this->assertSame(1900, $totals->get_subtotal()->get_amount_minor());
        $this->assertSame(1900, $totals->get_discount()->get_amount_minor());
    }

    public function test_explicit_consistent_totals_can_be_created(): void {
        $totals = new CommercePurchaseTotals(
            CommerceMoney::from_minor(10000, 'EUR'),
            CommerceMoney::from_minor(1500, 'EUR'),
            CommerceMoney::from_minor(8500, 'EUR')
        );

        $this->assertSame(8500, $totals->get_total()->get_amount_minor());
    }

    public function test_zero_totals_are_supported(): void {
        $totals = CommercePurchaseTotals::zero('rub');

        $this->assertSame('RUB', $totals->get_currency());
        $this->assertTrue($totals->is_free());
        $this->assertFalse($totals->has_discount());
    }

    public function test_empty_item_collection_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        CommercePurchaseTotals::from_items([]);
    }

    public function test_invalid_item_in_collection_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        CommercePurchaseTotals::from_items([
            $this->create_purchase_item('digital-product:8', 1, 1900, 0),
            new \stdClass(),
        ]);
    }

    public function test_mixed_item_currencies_are_rejected(): void {
        $this->expectException(\coding_exception::class);

        CommercePurchaseTotals::from_items([
            $this->create_purchase_item('digital-product:8', 1, 1900, 0, 'EUR'),
            $this->create_purchase_item('digital-product:9', 1, 1900, 0, 'RUB'),
        ]);
    }

    public function test_explicit_totals_require_one_currency(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseTotals(
            CommerceMoney::from_minor(10000, 'EUR'),
            CommerceMoney::from_minor(1000, 'RUB'),
            CommerceMoney::from_minor(9000, 'EUR')
        );
    }

    public function test_explicit_totals_must_be_consistent(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseTotals(
            CommerceMoney::from_minor(10000, 'EUR'),
            CommerceMoney::from_minor(1000, 'EUR'),
            CommerceMoney::from_minor(9500, 'EUR')
        );
    }

    public function test_discount_cannot_exceed_subtotal(): void {
        $this->expectException(\coding_exception::class);

        new CommercePurchaseTotals(
            CommerceMoney::from_minor(1000, 'EUR'),
            CommerceMoney::from_minor(1001, 'EUR'),
            CommerceMoney::zero('EUR')
        );
    }

    public function test_serialised_totals_preserve_minor_amounts(): void {
        $totals = new CommercePurchaseTotals(
            CommerceMoney::from_minor(10000, 'EUR'),
            CommerceMoney::from_minor(1500, 'EUR'),
            CommerceMoney::from_minor(8500, 'EUR')
        );

        $snapshot = $totals->to_array();

        $this->assertSame(10000, $snapshot['subtotal']['amountminor']);
        $this->assertSame(1500, $snapshot['discount']['amountminor']);
        $this->assertSame(8500, $snapshot['total']['amountminor']);
        $this->assertSame('EUR', $snapshot['total']['currency']);
    }

    private function create_purchase_item(
        string $reference,
        int $quantity,
        int $unitamountminor,
        int $discountminor,
        string $currency = 'EUR'
    ): CommercePurchaseItem {
        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            $reference,
            'Test item'
        );

        return new CommercePurchaseItem(
            $item,
            $quantity,
            CommerceMoney::from_minor($unitamountminor, $currency),
            CommerceMoney::from_minor($discountminor, $currency)
        );
    }
}
