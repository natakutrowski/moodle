<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;

final class commerce_796c2_native_amount_consumers_test extends advanced_testcase {
    public function test_compatibility_float_boundary_respects_currency_minor_units(): void {
        $this->assertSame(4490.0, CommerceCurrencyAmount::major_float_from_minor(4490, 'JPY'));
        $this->assertSame(149.125, CommerceCurrencyAmount::major_float_from_minor(149125, 'TND'));
        $this->assertSame(39.90, CommerceCurrencyAmount::major_float_from_minor(3990, 'EUR'));
    }

    public function test_fulfillment_context_exposes_currency_aware_major_amount(): void {
        $jpy = CommerceFulfillmentContext::confirmed(
            'PUR-JPY',
            'stripe',
            'txn-jpy',
            4490,
            'JPY',
            time()
        );
        $tnd = CommerceFulfillmentContext::confirmed(
            'PUR-TND',
            'stripe',
            'txn-tnd',
            149125,
            'TND',
            time()
        );

        $this->assertSame(4490.0, $jpy->get_amount_major());
        $this->assertSame(149.125, $tnd->get_amount_major());
        $this->assertSame(4490, $jpy->get_amount_minor());
        $this->assertSame('TND', $tnd->get_currency());
    }
}
