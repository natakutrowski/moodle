<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\currency\Currency;
use local_subscriptions\currency\CurrencyFormatter;

/**
 * Commerce 7.96B currency core consolidation tests.
 *
 * @covers \local_subscriptions\currency\Currency
 * @covers \local_subscriptions\currency\CurrencyFormatter
 * @covers \local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry
 * @covers \local_subscriptions\commerce\domain\value\CommerceMoney
 */
final class commerce_796b_currency_core_test extends advanced_testcase {

    public function test_currency_metadata_covers_initial_international_core(): void {
        $known = Currency::known_codes();

        foreach (['EUR', 'RUB', 'BYN', 'USD', 'CAD', 'AUD', 'GBP', 'JPY', 'AED', 'MAD', 'XOF', 'XAF', 'TND'] as $code) {
            $this->assertContains($code, $known);
        }

        $this->assertSame(0, Currency::minor_unit_exponent('JPY'));
        $this->assertSame(0, Currency::minor_unit_exponent('XOF'));
        $this->assertSame(3, Currency::minor_unit_exponent('TND'));
        $this->assertSame(2, Currency::minor_unit_exponent('USD'));
        $this->assertSame('🇪🇺', Currency::visual_marker('EUR'));
        $this->assertSame('🇯🇵', Currency::visual_marker('JPY'));
    }

    public function test_registry_separates_known_from_enabled_currencies(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,USD,AUD,JPY,NOPE', 'local_subscriptions');

        $registry = new CommerceCurrencyRegistry();

        $this->assertContains('BYN', $registry->known());
        $this->assertContains('TND', $registry->known());
        $enabled = $registry->enabled();
        sort($enabled);
        $this->assertSame(['AUD', 'EUR', 'JPY', 'USD'], $enabled);
    }

    public function test_registry_keeps_795_default_when_unconfigured(): void {
        $this->resetAfterTest();
        unset_config('commerce_enabled_currencies', 'local_subscriptions');

        $this->assertSame(['EUR', 'RUB'], (new CommerceCurrencyRegistry())->enabled());
    }

    public function test_currency_aware_money_conversion_uses_central_metadata(): void {
        $jpy = CommerceMoney::from_major_for_currency('149', 'JPY');
        $this->assertSame(149, $jpy->get_amount_minor());
        $this->assertSame('149', $jpy->get_amount_major_for_currency());

        $tnd = CommerceMoney::from_major_for_currency('149.125', 'TND');
        $this->assertSame(149125, $tnd->get_amount_minor());
        $this->assertSame('149.125', $tnd->get_amount_major_for_currency());
    }

    public function test_minor_formatter_respects_zero_two_and_three_digit_exponents(): void {
        $this->assertStringContainsString('149', CurrencyFormatter::format_minor(149, 'JPY'));
        $this->assertStringContainsString('149', CurrencyFormatter::format_minor(14900, 'EUR'));
        $this->assertStringContainsString('149', CurrencyFormatter::format_minor(149125, 'TND'));
    }
}
