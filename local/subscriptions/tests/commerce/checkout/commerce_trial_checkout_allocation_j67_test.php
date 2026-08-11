<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchaseBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSummaryBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutValidator;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * J6.7 regression coverage for Trial checkout amount locking.
 *
 * @covers \local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchaseBuilder
 */
final class commerce_trial_checkout_allocation_j67_test
        extends \advanced_testcase {

    public function test_trial_only_cart_keeps_already_discounted_total(): void {
        $trialitem = new CommerceCartItem(
            'COURSE_ACCESS.A2_GRAMMAR',
            39,
            1,
            [
                'operation' => 'trialconversion',
                'trialdiscountpercent' => 20,
                'trialproductsku' => 'COURSE_ACCESS.A2_GRAMMAR',
            ]
        );

        // The calculator has already converted 100 EUR into the final
        // 80 EUR Trial price before the checkout builder runs.
        $snapshot = $this->snapshot(
            [
                $this->calculated_item(
                    $trialitem,
                    'A2 Grammar',
                    8000
                ),
            ],
            8000,
            0,
            8000
        );

        $purchase = $this->build_purchase($snapshot);

        $this->assertSame(8000, $purchase->get_total_amount_minor());

        $items = $purchase->get_items();
        $this->assertCount(1, $items);
        $this->assertSame(8000, $items[0]->get_total_amount_minor());
        $this->assertSame(
            'trialconversion',
            $items[0]->get_metadata()['commerceoperation']
        );
    }

    public function test_trial_line_is_locked_while_normal_line_receives_cart_discount(): void {
        $trialitem = new CommerceCartItem(
            'COURSE_ACCESS.A2_GRAMMAR',
            39,
            1,
            [
                'operation' => 'trialconversion',
                'trialdiscountpercent' => 20,
            ]
        );
        $digitalitem = new CommerceCartItem(
            'DIGITAL.TEST',
            80,
            1
        );

        // Trial line is already final at 80 EUR.
        // Normal digital line is 20 EUR and receives a 10 EUR cart promotion.
        // Checkout target must therefore be 80 + 10 = 90 EUR.
        $snapshot = $this->snapshot(
            [
                $this->calculated_item(
                    $trialitem,
                    'A2 Grammar',
                    8000
                ),
                $this->calculated_item(
                    $digitalitem,
                    'Digital resource',
                    2000,
                    'digital_download'
                ),
            ],
            10000,
            1000,
            9000
        );

        $purchase = $this->build_purchase($snapshot);
        $items = $purchase->get_items();

        $this->assertSame(9000, $purchase->get_total_amount_minor());
        $this->assertSame(8000, $items[0]->get_total_amount_minor());
        $this->assertSame(1000, $items[1]->get_total_amount_minor());
    }

    private function build_purchase(
        CommerceCartSnapshot $snapshot
    ): \local_subscriptions\commerce\purchase\CommercePurchaseRequest {
        $context = new CommerceCheckoutContext(
            42,
            'EUR',
            'fr',
            'stripe',
            '/success',
            '/cancel',
            false
        );

        $summary = (
            new CommerceCheckoutSummaryBuilder(
                new CommerceCheckoutValidator()
            )
        )->build($snapshot, $context, 123456);

        $this->assertTrue($summary->is_valid());

        return (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer(
                42,
                'trial@example.test',
                'Trial',
                'Customer'
            ),
            'j67-trial-checkout'
        );
    }

    private function calculated_item(
        CommerceCartItem $item,
        string $name,
        int $amountminor,
        string $type = 'course_access'
    ): CommerceCalculatedCartItem {
        return new CommerceCalculatedCartItem(
            $item,
            $name,
            CommerceMoney::from_minor($amountminor, 'EUR'),
            CommerceMoney::from_minor($amountminor, 'EUR'),
            1,
            1,
            1,
            $type
        );
    }

    /**
     * @param CommerceCalculatedCartItem[] $items
     */
    private function snapshot(
        array $items,
        int $subtotalminor,
        int $discountminor,
        int $totalminor
    ): CommerceCartSnapshot {
        $cartitems = array_map(
            static fn(CommerceCalculatedCartItem $item): CommerceCartItem =>
                $item->get_item(),
            $items
        );

        $cart = new CommerceCart(
            '77777777777777777777777777777777',
            42,
            'EUR',
            $cartitems,
            [],
            1,
            1
        );

        return new CommerceCartSnapshot(
            $cart,
            $items,
            new CommerceCartTotals(
                CommerceMoney::from_minor($subtotalminor, 'EUR'),
                CommerceMoney::from_minor($discountminor, 'EUR'),
                CommerceMoney::zero('EUR'),
                CommerceMoney::from_minor($totalminor, 'EUR')
            ),
            123456
        );
    }
}
