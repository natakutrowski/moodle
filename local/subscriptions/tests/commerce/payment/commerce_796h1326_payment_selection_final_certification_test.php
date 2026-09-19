<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentPresentationPlanner;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentRoute;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\policy\CommercePaymentMarketSignalPolicy;
use local_subscriptions\commerce\payment\policy\CommercePaymentRecommendationProfileRegistry;

/**
 * H13.2.6 — final static/domain certification of intelligent payment selection.
 *
 * Manual E2E certification completed for EUR, USD, GBP and RUB after H13.2.5.
 * This suite freezes the architectural contracts that made those paths work.
 */
final class commerce_796h1326_payment_selection_final_certification_test extends \advanced_testcase {
    public function test_representative_currency_profiles_are_not_binary_eur_rub(): void {
        $registry = new CommercePaymentRecommendationProfileRegistry();

        foreach (['EUR', 'USD', 'GBP', 'RUB'] as $currency) {
            self::assertNotEmpty(
                $registry->order($currency, 'ZZ'),
                $currency
            );
        }

        self::assertSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('USD', 'US'), 0, 6)
        );
        self::assertSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('GBP', 'GB'), 0, 6)
        );
        self::assertNotSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('RUB', 'RU'), 0, 6)
        );
    }

    public function test_usd_klarna_guard_is_frozen(): void {
        self::assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'USD',
                'US'
            )
        );

        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'FR'
            )
        );

        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'GBP',
                'GB'
            )
        );
    }

    public function test_rub_native_methods_are_vpn_resilient_but_currency_bounded(): void {
        foreach ([
            CommercePaymentMethod::ALFA_PAY,
            CommercePaymentMethod::SBP,
            CommercePaymentMethod::SBERPAY,
            CommercePaymentMethod::MIR_PAY,
        ] as $method) {
            foreach (['RU', 'FR', 'DE', 'US', 'ZZ'] as $country) {
                self::assertTrue(
                    CommercePaymentMethodMarketEligibility::supports(
                        $method,
                        'RUB',
                        $country
                    ),
                    $method . '/' . $country
                );
            }

            foreach (['EUR', 'USD', 'GBP'] as $currency) {
                self::assertFalse(
                    CommercePaymentMethodMarketEligibility::supports(
                        $method,
                        $currency,
                        'RU'
                    ),
                    $method . '/' . $currency
                );
            }
        }
    }

    public function test_geo_is_advisory_except_for_real_market_constraints(): void {
        $policy = new CommercePaymentMarketSignalPolicy();

        self::assertTrue(
            $policy->country_is_authoritative_for(
                CommercePaymentMethod::KLARNA,
                'EUR'
            )
        );

        foreach ([
            [CommercePaymentMethod::CARD, 'EUR'],
            [CommercePaymentMethod::APPLE_PAY, 'EUR'],
            [CommercePaymentMethod::GOOGLE_PAY, 'EUR'],
            [CommercePaymentMethod::LINK, 'USD'],
            [CommercePaymentMethod::PAYPAL, 'GBP'],
            [CommercePaymentMethod::ALFA_PAY, 'RUB'],
            [CommercePaymentMethod::SBP, 'RUB'],
        ] as [$method, $currency]) {
            self::assertFalse(
                $policy->country_is_authoritative_for(
                    $method,
                    $currency
                ),
                $method
            );
        }
    }

    public function test_presentation_planner_preserves_recommendation_across_express_and_classic_routes(): void {
        $available = [
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::CARD,
                'EUR',
                ['stripe']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::PAYPAL,
                'EUR',
                ['paypal']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::GOOGLE_PAY,
                'EUR',
                ['stripe']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::APPLE_PAY,
                'EUR',
                ['stripe']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::LINK,
                'EUR',
                ['stripe']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::KLARNA,
                'EUR',
                ['stripe']
            ),
        ];

        $routes = [
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::CARD,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_INLINE
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::PAYPAL,
                'paypal',
                CommerceCheckoutPaymentRoute::SURFACE_HOSTED
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::KLARNA,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_INLINE
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::LINK,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_EXPRESS
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::GOOGLE_PAY,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_EXPRESS
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::APPLE_PAY,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_EXPRESS
            ),
        ];

        $plan = (new CommerceCheckoutPaymentPresentationPlanner())
            ->plan('FR', 'EUR', $available, $routes);

        self::assertSame(
            [
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethod::KLARNA,
            ],
            array_map(
                static fn(CommerceCheckoutPaymentRoute $route): string =>
                    $route->get_method(),
                $plan['orderedroutes']
            )
        );
    }

    public function test_checkout_runtime_keeps_single_recommendation_pipeline_before_surface_split(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $planner = strpos(
            $source,
            'CommerceCheckoutPaymentPresentationPlanner'
        );
        $split = strpos(
            $source,
            '$expressroutes ='
        );

        self::assertNotFalse($planner);
        self::assertNotFalse($split);
        self::assertLessThan($split, $planner);

        self::assertStringContainsString(
            '$orderedallmethods',
            $source
        );
        self::assertStringContainsString(
            '$presentationplan',
            $source
        );
        self::assertStringContainsString(
            "'recommended_method' =>",
            $source
        );
    }

    public function test_final_h132_source_contract_has_no_country_gate_for_rub_native_methods(): void {
        global $CFG;

        $eligibility = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentMethodMarketEligibility.php'
        );
        $profile = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/policy/'
            . 'CommercePaymentRecommendationProfileRegistry.php'
        );

        self::assertStringContainsString(
            'VPN/proxy',
            $eligibility
        );
        self::assertStringContainsString(
            "'USD' => self::GLOBAL_CARD",
            $profile
        );
        self::assertStringContainsString(
            "'GBP' => self::GLOBAL_CARD",
            $profile
        );
        self::assertStringContainsString(
            "'RUB' => self::RUB",
            $profile
        );
    }
}
