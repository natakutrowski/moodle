<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;

/**
 * Commerce 7.96B3 currency activation boundary tests.
 *
 * @covers \local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry
 */
final class commerce_796b3_currency_activation_test extends advanced_testcase {

    public function test_enabled_currency_is_distinct_from_known_currency(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,USD', 'local_subscriptions');

        $registry = new CommerceCurrencyRegistry();

        $this->assertTrue($registry->is_enabled('EUR'));
        $this->assertTrue($registry->is_enabled('usd'));
        $this->assertFalse($registry->is_enabled('JPY'));
        $this->assertSame('JPY', $registry->require_known('JPY'));
    }

    public function test_existing_disabled_currency_can_remain_visible_in_editor_options(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,USD', 'local_subscriptions');

        $options = (new CommerceCurrencyRegistry())->options_including(['JPY', 'NOPE']);

        $this->assertArrayHasKey('EUR', $options);
        $this->assertArrayHasKey('USD', $options);
        $this->assertArrayHasKey('JPY', $options);
        $this->assertArrayNotHasKey('NOPE', $options);
        $this->assertStringContainsString('🇯🇵', $options['JPY']);
    }

    public function test_disabled_currency_is_rejected_for_new_commercial_use(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,USD', 'local_subscriptions');

        $this->expectException(\coding_exception::class);
        (new CommerceCurrencyRegistry())->require_enabled('JPY');
    }
}
