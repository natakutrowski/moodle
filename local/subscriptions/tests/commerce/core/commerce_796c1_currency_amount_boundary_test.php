<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\CommerceCurrencyAmount;

final class commerce_796c1_currency_amount_boundary_test extends \advanced_testcase {
    public function test_major_input_uses_currency_minor_units(): void {
        $this->assertSame(4490, CommerceCurrencyAmount::from_major_input('4490', 'JPY')->get_amount_minor());
        $this->assertSame(149125, CommerceCurrencyAmount::from_major_input('149.125', 'TND')->get_amount_minor());
        $this->assertSame(3990, CommerceCurrencyAmount::from_major_input('39,90', 'EUR')->get_amount_minor());
    }

    public function test_minor_amount_is_restored_for_admin_input(): void {
        $this->assertSame('4490', CommerceCurrencyAmount::major_input_from_minor(4490, 'JPY'));
        $this->assertSame('149.125', CommerceCurrencyAmount::major_input_from_minor(149125, 'TND'));
        $this->assertSame('39.90', CommerceCurrencyAmount::major_input_from_minor(3990, 'EUR'));
    }

    public function test_too_many_decimal_places_are_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommerceCurrencyAmount::from_major_input('12.34', 'JPY');
    }
}
