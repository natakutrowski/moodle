<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPaymentRequestBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchaseBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSnapshot;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSummaryBuilder;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutValidator;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus;

final class commerce_795h01_unified_checkout_foundations_test extends advanced_testcase {
    public function test_summary_purchase_and_payment_keep_the_same_locked_total(): void {
        $snapshot = $this->cart_snapshot(10000, 2000, 8000);
        $context = new CommerceCheckoutContext(42, 'EUR', 'fr', 'stripe', '/success', '/cancel', false);
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))->build($snapshot, $context, 123456);
        $customer = new CommerceCustomer(42, 'nata@example.test', 'Nata', 'CampusFR');
        $purchase = (new CommerceCheckoutPurchaseBuilder())->build($summary, $customer, 'checkout-test');
        $payment = (new CommerceCheckoutPaymentRequestBuilder())->build($purchase);
        $checkoutsnapshot = new CommerceCheckoutSnapshot($summary, $purchase, $payment);

        $this->assertTrue($summary->is_valid());
        $this->assertSame(8000, $checkoutsnapshot->get_total_minor());
        $this->assertSame(8000, $purchase->get_total_amount_minor());
        $this->assertSame(8000, $payment->get_amount_minor());
        $this->assertSame(CommercePurchaseRequestStatus::PAYMENT_PENDING, $purchase->get_status());
        $this->assertSame('stripe', $payment->get_preferred_provider());
    }

    public function test_empty_cart_is_rejected_before_purchase_creation(): void {
        $cart = new CommerceCart('00000000000000000000000000000000', 42, 'EUR', [], [], 1, 1);
        $zero = CommerceMoney::zero('EUR');
        $snapshot = new CommerceCartSnapshot($cart, [], new CommerceCartTotals($zero, $zero, $zero, $zero), 1);
        $context = new CommerceCheckoutContext(42, 'EUR', 'fr', 'stripe', '/success', '/cancel', false);
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))->build($snapshot, $context, 1);

        $this->assertFalse($summary->is_valid());
        $this->expectException(\RuntimeException::class);
        (new CommerceCheckoutPurchaseBuilder())->build($summary, new CommerceCustomer(42, 'nata@example.test'));
    }


    public function test_rejected_promotion_warning_does_not_block_checkout(): void {
        $snapshot = $this->cart_snapshot(10000, 0, 10000, [
            new CommerceCartMessage(
                'promotion_customer_not_eligible',
                CommerceCartMessage::LEVEL_WARNING,
                ['code' => 'WELCOME']
            ),
        ]);
        $context = new CommerceCheckoutContext(42, 'EUR', 'fr', 'stripe', '/success', '/cancel', false);
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))->build($snapshot, $context, 123456);

        $this->assertTrue($summary->is_valid());
        $purchase = (new CommerceCheckoutPurchaseBuilder())->build(
            $summary,
            new CommerceCustomer(42, 'nata@example.test')
        );
        $this->assertSame(10000, $purchase->get_total_amount_minor());
    }

    public function test_non_promotion_warning_still_blocks_checkout(): void {
        $snapshot = $this->cart_snapshot(10000, 0, 10000, [
            new CommerceCartMessage('catalog_inconsistent', CommerceCartMessage::LEVEL_WARNING),
        ]);
        $context = new CommerceCheckoutContext(42, 'EUR', 'fr', 'stripe', '/success', '/cancel', false);
        $summary = (new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()))->build($snapshot, $context, 123456);

        $this->assertFalse($summary->is_valid());
        $this->expectExceptionMessage('cart_catalog_inconsistent');
        (new CommerceCheckoutPurchaseBuilder())->build($summary, new CommerceCustomer(42, 'nata@example.test'));
    }

    private function cart_snapshot(int $subtotal, int $discount, int $total, array $messages = []): CommerceCartSnapshot {
        $item = new CommerceCartItem('A1-FULL', 10, 1);
        $cart = new CommerceCart('11111111111111111111111111111111', 42, 'EUR', [$item], [], 1, 1);
        $calculated = new CommerceCalculatedCartItem(
            $item,
            'A1 Français complet',
            CommerceMoney::from_minor($subtotal, 'EUR'),
            CommerceMoney::from_minor($subtotal, 'EUR'),
            1,
            1,
            1,
            'course_access'
        );
        return new CommerceCartSnapshot(
            $cart,
            [$calculated],
            new CommerceCartTotals(
                CommerceMoney::from_minor($subtotal, 'EUR'),
                CommerceMoney::from_minor($discount, 'EUR'),
                CommerceMoney::zero('EUR'),
                CommerceMoney::from_minor($total, 'EUR')
            ),
            1,
            $messages
        );
    }
}
