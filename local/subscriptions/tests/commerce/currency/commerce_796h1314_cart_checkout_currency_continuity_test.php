<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1314_cart_checkout_currency_continuity_test extends \advanced_testcase {
    public function test_surface_service_now_accepts_engaged_journey_candidates(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/selection/'
            . 'CommerceCurrencySurfaceSelectionService.php'
        );

        self::assertStringContainsString(
            "string \$activecart = ''",
            $source
        );
        self::assertStringContainsString(
            "string \$activeguestcheckout = ''",
            $source
        );
        self::assertStringContainsString(
            'activecart: Currency::sanitize($activecart)',
            $source
        );
        self::assertStringContainsString(
            'activeguestcheckout: Currency::sanitize($activeguestcheckout)',
            $source
        );
    }

    public function test_journey_resolver_is_read_only_and_does_not_open_carts(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/selection/'
            . 'CommerceCurrencyJourneyStateResolver.php'
        );

        self::assertStringContainsString(
            'local_subscriptions_commerce_carts',
            $source
        );
        self::assertStringContainsString(
            'active_guest_checkout_currency',
            $source
        );
        self::assertStringContainsString(
            'active_cart_currency',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceCartRuntimeFactory',
            $source
        );
        self::assertStringNotContainsString(
            '->open(',
            $source
        );
        self::assertStringNotContainsString(
            '->save(',
            $source
        );
    }

    public function test_cart_and_checkout_use_same_h131_selection_stack(): void {
        global $CFG;

        foreach ([
            'cart.php',
            'commerce_checkout.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'CommerceCurrencySurfaceSelectionService',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'CommerceCurrencyJourneyStateResolver',
                $source,
                $relative
            );
            self::assertStringContainsString(
                '$activecartcurrency',
                $source,
                $relative
            );
            self::assertStringContainsString(
                '$activeguestcurrency',
                $source,
                $relative
            );
        }
    }

    public function test_checkout_no_longer_contains_rub_else_eur_currency_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringNotContainsString(
            "in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR'",
            $source
        );
    }

    public function test_explicit_currency_remains_higher_priority_than_engaged_state(): void {
        $this->resetAfterTest();

        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );

        $service =
            new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService();

        $result =
            $service->resolve(
                new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext(
                    available: ['EUR', 'RUB', 'USD', 'GBP'],
                    explicit: 'USD',
                    activecart: 'GBP',
                    activeguestcheckout: 'RUB',
                    sessionpreference: 'EUR',
                    marketdefault: 'EUR'
                )
            );

        self::assertSame(
            'USD',
            $result->get_currency()
        );
        self::assertSame(
            'explicit',
            $result->get_source_value()
        );
    }

    public function test_active_cart_beats_guest_checkout_and_preferences(): void {
        $this->resetAfterTest();

        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );

        $service =
            new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService();

        $result =
            $service->resolve(
                new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext(
                    available: ['EUR', 'RUB', 'USD', 'GBP'],
                    activecart: 'GBP',
                    activeguestcheckout: 'RUB',
                    sessionpreference: 'USD',
                    marketdefault: 'EUR'
                )
            );

        self::assertSame(
            'GBP',
            $result->get_currency()
        );
        self::assertSame(
            'active_cart',
            $result->get_source_value()
        );
    }

    public function test_guest_checkout_beats_preferences_when_no_active_cart_exists(): void {
        $this->resetAfterTest();

        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );

        $service =
            new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService();

        $result =
            $service->resolve(
                new \local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext(
                    available: ['EUR', 'RUB', 'USD', 'GBP'],
                    activeguestcheckout: 'RUB',
                    sessionpreference: 'USD',
                    marketdefault: 'GBP'
                )
            );

        self::assertSame(
            'RUB',
            $result->get_currency()
        );
        self::assertSame(
            'active_guest_checkout',
            $result->get_source_value()
        );
    }
}
