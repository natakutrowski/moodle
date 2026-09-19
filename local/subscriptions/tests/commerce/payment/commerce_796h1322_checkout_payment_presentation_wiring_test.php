<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentPresentationPlanner;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentRoute;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodAvailability;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

final class commerce_796h1322_checkout_payment_presentation_wiring_test extends \advanced_testcase {
    public function test_global_profile_orders_express_routes_before_standard_routes(): void {
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
                CommercePaymentMethod::GOOGLE_PAY,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_EXPRESS
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::APPLE_PAY,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_EXPRESS
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::LINK,
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
            ],
            array_map(
                static fn(CommerceCheckoutPaymentRoute $route): string =>
                    $route->get_method(),
                $plan['orderedroutes']
            )
        );

        self::assertSame(
            CommercePaymentMethod::APPLE_PAY,
            $plan['policy']->get_recommended_method()
        );
    }

    public function test_rub_profile_orders_local_routes_without_inventing_any(): void {
        $available = [
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::PAYPAL,
                'RUB',
                ['paypal']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::SBP,
                'RUB',
                ['alfa']
            ),
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::CARD,
                'RUB',
                ['alfa']
            ),
        ];

        $routes = [
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::PAYPAL,
                'paypal',
                CommerceCheckoutPaymentRoute::SURFACE_HOSTED
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::SBP,
                'alfa',
                CommerceCheckoutPaymentRoute::SURFACE_HOSTED
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::CARD,
                'alfa',
                CommerceCheckoutPaymentRoute::SURFACE_INLINE
            ),
        ];

        $plan = (new CommerceCheckoutPaymentPresentationPlanner())
            ->plan('RU', 'RUB', $available, $routes);

        self::assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::SBP,
                CommercePaymentMethod::PAYPAL,
            ],
            array_map(
                static fn(CommerceCheckoutPaymentRoute $route): string =>
                    $route->get_method(),
                $plan['orderedroutes']
            )
        );
    }

    public function test_route_sorting_is_stable_for_equal_or_unknown_rank(): void {
        $available = [
            new CommercePaymentMethodAvailability(
                CommercePaymentMethod::CARD,
                'EUR',
                ['stripe']
            ),
        ];

        $routes = [
            new CommerceCheckoutPaymentRoute(
                'future_a',
                'future',
                CommerceCheckoutPaymentRoute::SURFACE_HOSTED
            ),
            new CommerceCheckoutPaymentRoute(
                CommercePaymentMethod::CARD,
                'stripe',
                CommerceCheckoutPaymentRoute::SURFACE_INLINE
            ),
            new CommerceCheckoutPaymentRoute(
                'future_b',
                'future',
                CommerceCheckoutPaymentRoute::SURFACE_HOSTED
            ),
        ];

        $plan = (new CommerceCheckoutPaymentPresentationPlanner())
            ->plan('FR', 'EUR', $available, $routes);

        self::assertSame(
            [
                CommercePaymentMethod::CARD,
                'future_a',
                'future_b',
            ],
            array_map(
                static fn(CommerceCheckoutPaymentRoute $route): string =>
                    $route->get_method(),
                $plan['orderedroutes']
            )
        );
    }

    public function test_checkout_applies_policy_before_splitting_express_surface(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $planner = strpos(
            $source,
            'CommerceCheckoutPaymentPresentationPlanner'
        );
        $express = strpos(
            $source,
            '$expressroutes ='
        );

        self::assertNotFalse($planner);
        self::assertNotFalse($express);
        self::assertLessThan($express, $planner);

        self::assertStringContainsString(
            '$orderedallmethods',
            $source
        );
        self::assertStringContainsString(
            "'recommended_method' =>",
            $source
        );
    }
}
