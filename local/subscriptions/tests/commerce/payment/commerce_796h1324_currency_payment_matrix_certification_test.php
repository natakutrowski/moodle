<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentOrchestrator;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutPaymentPresentationPlanner;
use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * H13.2.4 — end-to-end domain certification of the representative
 * currency × payment-method matrix used by CampusFR.
 *
 * This test deliberately models provider capability separately from market
 * eligibility and recommendation. A method appears only when all layers agree.
 */
final class commerce_796h1324_currency_payment_matrix_certification_test extends advanced_testcase {
    /**
     * @dataProvider klarna_global_matrix_provider
     */
    public function test_klarna_capable_global_currency_matrix(
        string $currency,
        string $country
    ): void {
        self::assertSame(
            [
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethod::KLARNA,
            ],
            $this->plan($currency, $country),
            $currency . '/' . $country
        );
    }

    public static function klarna_global_matrix_provider(): array {
        return [
            'EUR France' => ['EUR', 'FR'],
            'GBP United Kingdom' => ['GBP', 'GB'],
        ];
    }

    public function test_usd_global_matrix_excludes_klarna_for_current_stripe_rail(): void {
        self::assertSame(
            [
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
            ],
            $this->plan('USD', 'US')
        );
    }

    public function test_rub_matrix_is_currency_led_even_with_vpn_country(): void {
        foreach (['RU', 'FR', 'DE', 'US', 'ZZ'] as $country) {
            self::assertSame(
                [
                    CommercePaymentMethod::CARD,
                    CommercePaymentMethod::SBP,
                    CommercePaymentMethod::ALFA_PAY,
                    CommercePaymentMethod::PAYPAL,
                ],
                $this->plan(
                    'RUB',
                    $country
                ),
                'RUB behind detected country ' . $country
            );
        }
    }

    public function test_russian_ip_does_not_inject_rub_methods_into_eur(): void {
        self::assertSame(
            [
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
            ],
            $this->plan(
                'EUR',
                'RU'
            )
        );
    }

    public function test_klarna_is_removed_when_customer_country_is_not_eligible(): void {
        self::assertContains(
            CommercePaymentMethod::KLARNA,
            $this->plan('EUR', 'FR')
        );

        self::assertNotContains(
            CommercePaymentMethod::KLARNA,
            $this->plan('EUR', 'RU')
        );
    }

    public function test_unimplemented_rub_methods_are_not_invented_by_recommendation(): void {
        $rub = $this->plan(
            'RUB',
            'RU'
        );

        self::assertNotContains(
            CommercePaymentMethod::SBERPAY,
            $rub
        );
        self::assertNotContains(
            CommercePaymentMethod::MIR_PAY,
            $rub
        );
    }

    public function test_provider_currency_capability_remains_authoritative(): void {
        $resolver =
            $this->availability();

        self::assertFalse(
            $resolver
                ->method(
                    'RUB',
                    CommercePaymentMethod::APPLE_PAY,
                    'RU'
                )
                ->is_available()
        );

        self::assertFalse(
            $resolver
                ->method(
                    'EUR',
                    CommercePaymentMethod::SBP,
                    'FR'
                )
                ->is_available()
        );
    }

    public function test_matrix_covers_more_than_binary_eur_rub_logic(): void {
        foreach (
            [
                ['EUR', 'FR'],
                ['USD', 'US'],
                ['GBP', 'GB'],
                ['RUB', 'RU'],
            ]
            as [$currency, $country]
        ) {
            self::assertNotEmpty(
                $this->plan(
                    $currency,
                    $country
                )
            );
        }
    }

    /**
     * @return string[]
     */
    private function plan(
        string $currency,
        string $country
    ): array {
        $availability =
            $this->availability();

        $available =
            $availability->available(
                $currency,
                $country
            );

        $routes =
            (
                new CommerceCheckoutPaymentOrchestrator(
                    $availability
                )
            )->routes(
                $currency,
                $country
            );

        $plan =
            (
                new CommerceCheckoutPaymentPresentationPlanner()
            )->plan(
                $country,
                $currency,
                $available,
                $routes
            );

        return array_values(
            array_map(
                static fn($route): string =>
                    $route->get_method(),
                $plan['orderedroutes']
            )
        );
    }

    private function availability():
        CommercePaymentAvailabilityResolver {
        return new CommercePaymentAvailabilityResolver(
            new CommercePaymentProviderRegistry([
                // Stripe side of the CampusFR matrix.
                $this->provider(
                    'stripe',
                    100,
                    ['EUR', 'USD', 'GBP'],
                    [
                        CommercePaymentMethod::CARD,
                        CommercePaymentMethod::APPLE_PAY,
                        CommercePaymentMethod::GOOGLE_PAY,
                        CommercePaymentMethod::LINK,
                        CommercePaymentMethod::KLARNA,
                    ]
                ),

                // Alfa side: only methods that are actually executable today.
                $this->provider(
                    'alfa',
                    90,
                    ['RUB'],
                    [
                        CommercePaymentMethod::CARD,
                        CommercePaymentMethod::ALFA_PAY,
                        CommercePaymentMethod::SBP,
                    ]
                ),

                // PayPal is a cross-currency fallback in the representative
                // H13.2 certification matrix.
                $this->provider(
                    'paypal',
                    80,
                    ['EUR', 'USD', 'GBP', 'RUB'],
                    [
                        CommercePaymentMethod::PAYPAL,
                    ]
                ),
            ])
        );
    }

    private function provider(
        string $key,
        int $priority,
        array $currencies,
        array $methods
    ): CommercePaymentProvider {
        return new class(
            $key,
            $priority,
            $currencies,
            $methods
        ) implements CommercePaymentProvider {
            public function __construct(
                private readonly string $key,
                private readonly int $priority,
                private readonly array $currencies,
                private readonly array $methods
            ) {
            }

            public function get_key(): string {
                return $this->key;
            }

            public function get_priority(): int {
                return $this->priority;
            }

            public function is_available(): bool {
                return true;
            }

            public function get_capabilities():
                CommercePaymentProviderCapabilities {
                return new CommercePaymentProviderCapabilities(
                    $this->currencies,
                    true,
                    false,
                    false,
                    false,
                    true,
                    [],
                    $this->methods
                );
            }

            public function supports(
                CommercePaymentRequest $request
            ): bool {
                $capabilities =
                    $this->get_capabilities();

                if (
                    !$capabilities
                        ->supports_currency(
                            $request->get_currency()
                        )
                ) {
                    return false;
                }

                $method =
                    $request
                        ->get_preferred_payment_method();

                return $method === null
                    || $capabilities
                        ->supports_payment_method(
                            $method
                        );
            }

            public function validate(
                CommercePaymentRequest $request
            ): CommercePaymentProviderValidationResult {
                return CommercePaymentProviderValidationResult::valid();
            }

            public function initialize(
                CommercePaymentRequest $request,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception(
                    'Not used by H13.2.4 certification.'
                );
            }

            public function retrieve(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception(
                    'Not used by H13.2.4 certification.'
                );
            }

            public function cancel(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception(
                    'Not used by H13.2.4 certification.'
                );
            }
        };
    }
}
