<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\crm\CommerceCustomerCrmAdapter;
use local_subscriptions\commerce\customer\crm\CommerceCustomerTimelineCollector;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerIdentity;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerMetrics;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPayment;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPurchase;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerSnapshot;
use local_subscriptions\currency\CurrencyFormatter;

/** Tests 7.96C7 customer/CRM currency presentation boundaries. */
final class commerce_796c7_customer_currency_surfaces_test extends advanced_testcase {
    public function test_customer_timeline_uses_real_minor_units(): void {
        $payment = new CommerceCustomerPayment(
            11,
            7,
            1,
            'paid',
            'JPY',
            4490,
            'stripe',
            'provider-ref',
            'tx-ref',
            1001,
            1000,
            1001
        );
        $purchase = new CommerceCustomerPurchase(
            7,
            'uuid-7',
            'PUR-7',
            'CF-7',
            'digital',
            'paid',
            'JPY',
            4490,
            42,
            'student@example.test',
            1000,
            1001,
            [['label' => 'Produit test']],
            [$payment],
            []
        );
        $snapshot = $this->snapshot([$purchase], [$payment], ['JPY' => 4490]);

        $events = (new CommerceCustomerTimelineCollector())->collect($snapshot);
        $expected = CurrencyFormatter::format_minor_code(4490, 'JPY');
        self::assertCount(2, $events);
        self::assertStringContainsString($expected, $events[0]->description);
        self::assertStringContainsString($expected, $events[1]->description);
    }

    public function test_customer_purchase_rows_keep_three_decimal_currency_boundary(): void {
        $purchase = new CommerceCustomerPurchase(
            8,
            'uuid-8',
            'PUR-8',
            'CF-8',
            'digital',
            'payment_pending',
            'TND',
            14925,
            42,
            'student@example.test',
            1000,
            1001,
            [['label' => 'Produit test']],
            [],
            []
        );
        $snapshot = $this->snapshot([$purchase], [], ['TND' => 14925]);

        $rows = (new CommerceCustomerCrmAdapter())->purchase_rows($snapshot);
        self::assertCount(1, $rows);
        self::assertSame(14925, $rows[0]->totalminor);
        self::assertSame('TND', $rows[0]->currency);
        self::assertSame(14.925, $rows[0]->total);
    }

    public function test_reported_admin_surfaces_are_connected_to_currency_core(): void {
        $root = dirname(__DIR__, 3);

        $productview = file_get_contents($root . '/admin/commerce/products/view.php');
        self::assertIsString($productview);
        self::assertStringContainsString('CommerceCurrencyLabelFormatter::format', $productview);
        self::assertStringContainsString('CommerceCurrencyRegistry', $productview);

        $unfinished = file_get_contents($root . '/admin/commerce/unfinished-checkouts/index.php');
        self::assertIsString($unfinished);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $unfinished);

        $merge = file_get_contents(
            $root . '/classes/commerce/customer/merge/CommerceCustomerMergeFinalStateRenderer.php'
        );
        self::assertIsString($merge);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $merge);
    }

    /**
     * @param CommerceCustomerPurchase[] $purchases
     * @param CommerceCustomerPayment[] $payments
     * @param array<string,int> $revenue
     */
    private function snapshot(array $purchases, array $payments, array $revenue): CommerceCustomerSnapshot {
        return new CommerceCustomerSnapshot(
            new CommerceCustomerIdentity(42, 'student@example.test'),
            $purchases,
            $payments,
            [],
            new CommerceCustomerMetrics(
                count($purchases),
                0,
                count($payments),
                0,
                0,
                0,
                [],
                [],
                [],
                [],
                [],
                $revenue,
                null,
                null,
                null
            )
        );
    }
}
