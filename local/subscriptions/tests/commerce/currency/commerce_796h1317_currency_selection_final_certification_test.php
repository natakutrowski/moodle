<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionContext;
use local_subscriptions\commerce\currency\selection\CommerceCurrencySelectionService;

final class commerce_796h1317_currency_selection_final_certification_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        set_config(
            'commerce_enabled_currencies',
            'EUR,RUB,USD,GBP',
            'local_subscriptions'
        );
    }

    public function test_h131_priority_contract_is_frozen(): void {
        $service = new CommerceCurrencySelectionService();

        $result = $service->resolve(
            new CommerceCurrencySelectionContext(
                available: ['EUR', 'RUB', 'USD', 'GBP'],
                explicit: 'USD',
                activecart: 'GBP',
                activeguestcheckout: 'RUB',
                userpreference: 'EUR',
                sessionpreference: 'GBP',
                marketdefault: 'RUB',
                commercedefault: 'EUR'
            )
        );

        self::assertSame('USD', $result->get_currency());
        self::assertSame('explicit', $result->get_source_value());

        $result = $service->resolve(
            new CommerceCurrencySelectionContext(
                available: ['EUR', 'RUB', 'USD', 'GBP'],
                activecart: 'GBP',
                activeguestcheckout: 'RUB',
                userpreference: 'USD',
                sessionpreference: 'EUR',
                marketdefault: 'RUB',
                commercedefault: 'EUR'
            )
        );

        self::assertSame('GBP', $result->get_currency());
        self::assertSame('active_cart', $result->get_source_value());

        $result = $service->resolve(
            new CommerceCurrencySelectionContext(
                available: ['EUR', 'RUB', 'USD', 'GBP'],
                activeguestcheckout: 'RUB',
                userpreference: 'USD',
                sessionpreference: 'GBP',
                marketdefault: 'EUR',
                commercedefault: 'EUR'
            )
        );

        self::assertSame('RUB', $result->get_currency());
        self::assertSame('active_guest_checkout', $result->get_source_value());
    }

    public function test_public_surfaces_have_no_legacy_binary_currency_fallback(): void {
        global $CFG;

        foreach ([
            'digital_catalog.php',
            'storefront_product.php',
            'showroom.php',
            'cart.php',
            'cart_action.php',
            'commerce_checkout.php',
            'offer.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString(
                "['RU', 'BY'], true) ? 'RUB' : 'EUR'",
                $source,
                $relative
            );
        }
    }

    public function test_core_public_surfaces_use_shared_selection_services(): void {
        global $CFG;

        foreach ([
            'digital_catalog.php',
            'storefront_product.php',
            'showroom.php',
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
        }
    }

    public function test_disabled_currency_cannot_leak_from_catalog_selector(): void {
        global $CFG;

        $catalog = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        self::assertStringContainsString(
            'CommerceCurrencyAvailabilityService',
            $catalog
        );
        self::assertStringContainsString(
            'enabled_from(',
            $catalog
        );
    }

    public function test_market_layer_remains_multi_market_and_language_independent(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/market/'
            . 'CommerceMarketCurrencyRecommendationService.php'
        );

        foreach ([
            "'FR' => 'EUR'",
            "'RU' => 'RUB'",
            "'US' => 'USD'",
            "'GB' => 'GBP'",
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString('current_language', $source);
    }

    public function test_personal_offer_currency_is_constrained_by_offer_scope(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferCurrencySelectionService.php'
        );

        self::assertStringContainsString('get_available_currencies(', $source);
        self::assertStringContainsString('new CommerceCurrencySelectionService()', $source);
    }

    public function test_personal_offer_guest_path_has_no_otp_friction(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            "identity_proof' => 'personal_offer_signed_link'",
            $checkout
        );
        self::assertStringContainsString(
            'initialisePersonalOfferIdentityCompletion',
            $js
        );

        $start = strpos(
            $js,
            'const initialisePersonalOfferIdentityCompletion'
        );
        self::assertNotFalse($start);

        $block = substr($js, $start, 6500);

        self::assertStringNotContainsString(
            'guest_identity_otp_start.php',
            $block
        );
        self::assertStringContainsString(
            'setGuestPaymentGate(form, false)',
            $block
        );
    }

    public function test_individual_personal_offer_can_target_showroom_without_campaign(): void {
        global $CFG;

        $manager = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignManager.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/personaloffer/service/'
            . 'CommercePersonalOfferDestinationResolver.php'
        );

        self::assertStringContainsString('individual_destination', $manager);
        self::assertStringContainsString('individual_showroom_id', $manager);
        self::assertStringContainsString(
            'CommercePersonalOfferIndividualDestinationService',
            $resolver
        );
    }

    public function test_cart_and_guest_checkout_continuity_guards_remain_present(): void {
        global $CFG;

        foreach ([
            'cart.php',
            'commerce_checkout.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
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
}
