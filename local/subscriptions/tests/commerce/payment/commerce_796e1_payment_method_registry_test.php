<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e1_payment_method_registry_test extends advanced_testcase {
    public function test_registry_resolves_provider_from_method_capability(): void {
        $card = $this->provider(
            'cardpsp',
            10,
            ['EUR'],
            [CommercePaymentMethod::CARD]
        );
        $wallet = $this->provider(
            'walletpsp',
            20,
            ['EUR'],
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::APPLE_PAY,
            ]
        );

        $registry = new CommercePaymentProviderRegistry([
            $card,
            $wallet,
        ]);

        $request = $this->request(
            CommercePaymentMethod::APPLE_PAY
        );

        $this->assertSame(
            ['card', 'apple_pay'],
            $registry->available_payment_methods(
                $this->request(null)
            )
        );
        $this->assertSame(
            'walletpsp',
            $registry->resolve($request)->get_key()
        );
    }

    private function request(?string $method): CommercePaymentRequest {
        return new CommercePaymentRequest(
            'PAY.' . ($method ?? 'AUTO'),
            new CommercePaymentCustomer(
                null,
                'test@example.test',
                'Test'
            ),
            [
                new CommercePaymentLine(
                    'SKU',
                    'Product',
                    1,
                    1000,
                    'EUR'
                ),
            ],
            'EUR',
            1000,
            null,
            'https://example.test/return',
            'https://example.test/cancel',
            [],
            null,
            $method
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
