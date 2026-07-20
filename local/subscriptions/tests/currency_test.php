<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\currency\Currency;
use local_subscriptions\currency\CurrencyFormatter;
use local_subscriptions\currency\CurrencyPreferenceRepository;
use local_subscriptions\currency\CurrencyPreferenceService;

/**
 * Tests for generic CRM currency handling.
 *
 * @covers \local_subscriptions\currency\Currency
 * @covers \local_subscriptions\currency\CurrencyFormatter
 * @covers \local_subscriptions\currency\CurrencyPreferenceRepository
 * @covers \local_subscriptions\currency\CurrencyPreferenceService
 */
final class currency_test extends advanced_testcase {

    public function test_currency_normalization(): void {
        $this->assertSame(
            'EUR',
            Currency::normalize(' eur ')
        );

        $this->assertSame(
            'CHF',
            Currency::sanitize('chf')
        );

        $this->assertSame(
            '',
            Currency::sanitize('EURO')
        );

        $this->assertSame(
            '',
            Currency::sanitize('12')
        );
    }

    public function test_known_currency_symbols(): void {
        $this->assertSame(
            '€',
            Currency::display_symbol('EUR')
        );

        $this->assertSame(
            '₽',
            Currency::display_symbol('RUB')
        );

        $this->assertSame(
            '£',
            Currency::display_symbol('GBP')
        );

        $this->assertSame(
            'CHF',
            Currency::display_symbol('CHF')
        );
    }

    public function test_ambiguous_symbols_use_iso_code(): void {
        $this->assertSame(
            'USD',
            Currency::display_symbol('USD')
        );

        $this->assertSame(
            'CAD',
            Currency::display_symbol('CAD')
        );

        $this->assertSame(
            'AUD',
            Currency::display_symbol('AUD')
        );
    }

    public function test_unknown_valid_currency_uses_iso_code(): void {
        $this->assertSame(
            'PLN',
            Currency::display_symbol('PLN')
        );

        $this->assertSame(
            2,
            Currency::decimals('PLN')
        );
    }

    public function test_currency_formatter_contains_currency(): void {
        $formatted = CurrencyFormatter::format(
            1234.50,
            'EUR'
        );

        $this->assertStringContainsString(
            '€',
            $formatted
        );

        $usd = CurrencyFormatter::format(
            1234.50,
            'USD'
        );

        $this->assertStringContainsString(
            'USD',
            $usd
        );
    }

    public function test_currency_preference_resolution(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $service = new CurrencyPreferenceService();

        $this->assertSame(
            'EUR',
            $service->resolve(
                (int)$user->id,
                ['RUB', 'EUR', 'USD']
            )
        );

        $this->assertTrue(
            $service->save(
                (int)$user->id,
                'USD',
                ['RUB', 'EUR', 'USD']
            )
        );

        $this->assertSame(
            'USD',
            $service->resolve(
                (int)$user->id,
                ['RUB', 'EUR', 'USD']
            )
        );
    }

    public function test_unavailable_preference_falls_back(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        (new CurrencyPreferenceRepository())->set(
            (int)$user->id,
            'CHF'
        );

        $service = new CurrencyPreferenceService();

        $this->assertSame(
            'EUR',
            $service->resolve(
                (int)$user->id,
                ['EUR', 'USD']
            )
        );
    }

    public function test_first_available_currency_is_used_without_eur(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $service = new CurrencyPreferenceService();

        $this->assertSame(
            'CHF',
            $service->resolve(
                (int)$user->id,
                ['USD', 'CHF']
            )
        );
    }
}