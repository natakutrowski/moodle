<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e2_payment_availability_resolver_test extends advanced_testcase {
    public function test_resolver_separates_method_from_provider_and_currency(): void {
        $registry = new CommercePaymentProviderRegistry([
            $this->provider(
                'stripe',
                100,
                ['EUR', 'USD'],
                [
                    CommercePaymentMethod::CARD,
                    CommercePaymentMethod::APPLE_PAY,
                ]
            ),
            $this->provider(
                'paypal',
                90,
                ['EUR', 'USD'],
                [
                    CommercePaymentMethod::PAYPAL,
                ]
            ),
            $this->provider(
                'alfa',
                80,
                ['RUB'],
                [
                    CommercePaymentMethod::CARD,
                ]
            ),
        ]);

        $resolver = new CommercePaymentAvailabilityResolver(
            $registry
        );

        $this->assertSame(
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::PAYPAL,
            ],
            array_map(
                static fn($item): string =>
                    $item->get_method(),
                $resolver->available('EUR')
            )
        );

        $this->assertSame(
            'stripe',
            $resolver
                ->method('EUR', CommercePaymentMethod::CARD)
                ->get_preferred_provider_key()
        );

        $this->assertSame(
            ['alfa'],
            $resolver
                ->method('RUB', CommercePaymentMethod::CARD)
                ->get_provider_keys()
        );

        $this->assertFalse(
            $resolver
                ->method('RUB', CommercePaymentMethod::APPLE_PAY)
                ->is_available()
        );

        $this->assertSame([], $resolver->available('TND'));
    }

    public function test_provider_priority_only_breaks_ties_inside_same_method(): void {
        $registry = new CommercePaymentProviderRegistry([
            $this->provider(
                'alfa',
                50,
                ['EUR'],
                [CommercePaymentMethod::CARD]
            ),
            $this->provider(
                'stripe',
                100,
                ['EUR'],
                [CommercePaymentMethod::CARD]
            ),
        ]);

        $card = (new CommercePaymentAvailabilityResolver($registry))
            ->method('EUR', CommercePaymentMethod::CARD);

        $this->assertSame(
            ['stripe', 'alfa'],
            $card->get_provider_keys()
        );
        $this->assertSame(
            'stripe',
            $card->get_preferred_provider_key()
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
                $capabilities = $this->get_capabilities();

                if (
                    !$capabilities
                        ->supports_currency(
                            $request->get_currency()
                        )
                ) {
                    return false;
                }

                $method = $request->get_preferred_payment_method();

                return $method === null
                    || $capabilities
                        ->supports_payment_method($method);
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
                throw new \coding_exception('Not used.');
            }

            public function retrieve(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception('Not used.');
            }

            public function cancel(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                throw new \coding_exception('Not used.');
            }
        };
    }
}
