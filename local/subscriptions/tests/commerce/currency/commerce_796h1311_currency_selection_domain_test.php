<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionSource;

final class commerce_796h1311_currency_selection_domain_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );
    }

    public function test_explicit_choice_wins_over_every_other_candidate(): void {
        $result = $this->resolve(
            explicit: 'GBP',
            activecart: 'USD',
            activeguestcheckout: 'RUB',
            userpreference: 'EUR',
            sessionpreference: 'EUR',
            marketdefault: 'USD'
        );

        self::assertSame('GBP', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::EXPLICIT,
            $result->get_source()
        );
    }

    public function test_active_cart_wins_over_checkout_and_preferences(): void {
        $result = $this->resolve(
            activecart: 'USD',
            activeguestcheckout: 'GBP',
            userpreference: 'EUR',
            marketdefault: 'RUB'
        );

        self::assertSame('USD', $result->get_currency());
        self::assertSame('active_cart', $result->get_source_value());
    }

    public function test_active_guest_checkout_prevents_automatic_market_switch(): void {
        $result = $this->resolve(
            activeguestcheckout: 'GBP',
            marketdefault: 'RUB'
        );

        self::assertSame('GBP', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::ACTIVE_GUEST_CHECKOUT,
            $result->get_source()
        );
    }

    public function test_user_preference_wins_over_session_and_market(): void {
        $result = $this->resolve(
            userpreference: 'USD',
            sessionpreference: 'GBP',
            marketdefault: 'EUR'
        );

        self::assertSame('USD', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::USER_PREFERENCE,
            $result->get_source()
        );
    }

    public function test_session_preference_wins_over_market(): void {
        $result = $this->resolve(
            sessionpreference: 'GBP',
            marketdefault: 'EUR'
        );

        self::assertSame('GBP', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::SESSION_PREFERENCE,
            $result->get_source()
        );
    }

    /**
     * @dataProvider market_currency_provider
     */
    public function test_market_candidate_is_currency_agnostic(
        string $marketcurrency
    ): void {
        $result = $this->resolve(
            marketdefault: $marketcurrency
        );

        self::assertSame(
            $marketcurrency,
            $result->get_currency()
        );
        self::assertSame(
            CommerceCurrencySelectionSource::MARKET_DEFAULT,
            $result->get_source()
        );
    }

    public static function market_currency_provider(): array {
        return [
            'EUR' => ['EUR'],
            'RUB' => ['RUB'],
            'USD' => ['USD'],
            'GBP' => ['GBP'],
        ];
    }

    public function test_unavailable_explicit_currency_is_ignored(): void {
        $result = $this->resolve(
            available: ['EUR', 'USD'],
            explicit: 'GBP',
            marketdefault: 'USD'
        );

        self::assertSame('USD', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::MARKET_DEFAULT,
            $result->get_source()
        );
    }

    public function test_disabled_currency_is_never_selected(): void {
        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,GBP',
            'local_subscriptions'
        );

        $result = $this->resolve(
            explicit: 'USD',
            sessionpreference: 'GBP'
        );

        self::assertSame('GBP', $result->get_currency());
    }

    public function test_commerce_default_is_used_after_all_preferences(): void {
        $result = $this->resolve(
            commercedefault: 'EUR'
        );

        self::assertSame('EUR', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::COMMERCE_DEFAULT,
            $result->get_source()
        );
    }

    public function test_first_available_is_deterministic_last_resort(): void {
        $result = $this->resolve(
            available: ['GBP', 'USD'],
            commercedefault: 'EUR'
        );

        self::assertSame('GBP', $result->get_currency());
        self::assertSame(
            CommerceCurrencySelectionSource::COMMERCE_DEFAULT,
            $result->get_source()
        );
    }



    public function test_surface_available_order_is_preserved_after_registry_filtering(): void {
        $result = $this->resolve(
            available: ['GBP', 'USD'],
            commercedefault: 'EUR'
        );

        self::assertSame(
            'GBP',
            $result->get_currency()
        );
    }

    public function test_selection_sources_are_not_used_as_php_array_keys(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/selection/'
            . 'CommerceCurrencySelectionService.php'
        );

        self::assertStringContainsString(
            'foreach ($candidates as [$source, $candidate])',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceCurrencySelectionSource::EXPLICIT =>',
            $source
        );
    }

    private function resolve(
        array $available = ['EUR', 'RUB', 'USD', 'GBP'],
        string $explicit = '',
        string $activecart = '',
        string $activeguestcheckout = '',
        string $userpreference = '',
        string $sessionpreference = '',
        string $marketdefault = '',
        string $commercedefault = 'EUR'
    ): \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionResult {
        return (
            new CommerceCurrencySelectionService()
        )->resolve(
            new CommerceCurrencySelectionContext(
                available: $available,
                explicit: $explicit,
                activecart: $activecart,
                activeguestcheckout: $activeguestcheckout,
                userpreference: $userpreference,
                sessionpreference: $sessionpreference,
                marketdefault: $marketdefault,
                commercedefault: $commercedefault
            )
        );
    }
}
