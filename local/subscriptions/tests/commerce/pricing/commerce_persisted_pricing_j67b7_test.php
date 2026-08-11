<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;

/** J6.7B7 persisted commercial pricing. */
final class commerce_persisted_pricing_j67b7_test
        extends \advanced_testcase {

    public function test_upgrade_pricing_reconciles_invoice_totals(): void {
        $presenter = new CommercePersistedCommercialPricingPresenter();

        $item = $presenter->item(
            [
                'pricing_initial_total_minor' => 20000,
                'pricing_promotion_total_minor' => 3000,
                'pricing_trial_discount_total_minor' => 3400,
                'pricing_owned_credit_total_minor' => 8000,
                'pricing_final_total_minor' => 5600,
                'pricing_total_reduction_minor' => 14400,
                'pricing_operation' => 'upgrade',
                'pricing_upgrade_from_label' => 'A2 Grammar',
            ],
            5600,
            0,
            5600,
            1
        );

        $this->assertTrue($item['haspricing']);
        $this->assertSame(20000, $item['initialminor']);
        $this->assertSame(3000, $item['promotionminor']);
        $this->assertSame(3400, $item['trialminor']);
        $this->assertSame(8000, $item['creditminor']);
        $this->assertSame(5600, $item['finalminor']);

        $order = $presenter->order(
            [
                'cart_list_total_minor' => 20000,
                'cart_product_promotion_minor' => 3000,
                'cart_trial_discount_minor' => 3400,
                'cart_owned_credit_minor' => 8000,
                'cart_discount_minor' => 14400,
            ],
            [$item],
            5600
        );

        $this->assertSame(14400, $order['totalreductionminor']);
        $this->assertSame(5600, $order['paidminor']);
    }

    public function test_old_native_order_uses_compatible_fallback(): void {
        $presenter = new CommercePersistedCommercialPricingPresenter();

        $item = $presenter->item(
            [
                'locked_list_total_minor' => 10000,
                'locked_trial_discount_minor' => 2000,
                'locked_total_minor' => 8000,
                'locked_total_discount_minor' => 2000,
            ],
            8000,
            0,
            8000
        );

        $this->assertSame(10000, $item['initialminor']);
        $this->assertSame(2000, $item['trialminor']);
        $this->assertSame(8000, $item['finalminor']);
    }
}
