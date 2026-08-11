<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\pricing\CommerceCommercialPriceBreakdown;

final class commerce_commercial_price_breakdown_test
        extends \advanced_testcase {

    public function test_upgrade_chain_reconciles_in_customer_order(): void {
        $pricing = new CommerceCommercialPriceBreakdown(
            'EUR',
            1,
            20000,
            3000,
            17000,
            3400,
            13600,
            8000,
            5600,
            15,
            20,
            'upgrade',
            'A2 Grammar',
            'A2 Full'
        );

        $this->assertSame(20000, $pricing->get_initial_total_minor());
        $this->assertSame(3000, $pricing->get_promotion_total_minor());
        $this->assertSame(3400, $pricing->get_trial_discount_total_minor());
        $this->assertSame(8000, $pricing->get_owned_credit_total_minor());
        $this->assertSame(5600, $pricing->get_final_total_minor());
        $this->assertSame(14400, $pricing->get_total_reduction_minor());
        $this->assertSame(
            'commercial_breakdown_v2',
            $pricing->to_metadata()['pricing_schema']
        );
    }

    public function test_invalid_chain_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommerceCommercialPriceBreakdown(
            'EUR',
            1,
            20000,
            3000,
            16000,
            0,
            16000,
            8000,
            8000
        );
    }
}
