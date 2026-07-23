<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderConflictException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderNotFoundException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderUnavailableException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Tests for the Commerce payment provider registry.
 *
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry
 */
final class commerce_payment_provider_registry_test
    extends advanced_testcase {

    public function test_preferred_provider_is_resolved():
        void {
        $stripe = $this->create_provider(
            'stripe',
            100,
            true,
            [
                'EUR',
            ]
        );

        $alfa = $this->create_provider(
            'alfa',
            50,
            true,
            [
                'EUR',
                'RUB',
            ]
        );

        $registry =
            new CommercePaymentProviderRegistry([
                $stripe,
                $alfa,
            ]);

        $resolved = $registry->resolve(
            $this->create_request(
                'EUR',
                'alfa'
            )
        );

        $this->assertSame(
            $alfa,
            $resolved
        );
    }

    public function test_highest_priority_provider_is_selected():
        void {
        $stripe = $this->create_provider(
            'stripe',
            100,
            true,
            [
                'EUR',
            ]
        );

        $alfa = $this->create_provider(
            'alfa',
            50,
            true,
            [
                'EUR',
            ]
        );

        $registry =
            new CommercePaymentProviderRegistry([
                $stripe,
                $alfa,
            ]);

        $resolved = $registry->resolve(
            $this->create_request('EUR')
        );

        $this->assertSame(
            $stripe,
            $resolved
        );
    }

    public function test_currency_filters_provider_candidates():
        void {
        $stripe = $this->create_provider(
            'stripe',
            100,
            true,
            [
                'EUR',
            ]
        );

        $alfa = $this->create_provider(
            'alfa',
            50,
            true,
            [
                'RUB',
            ]
        );

        $registry =
            new CommercePaymentProviderRegistry([
                $stripe,
                $alfa,
            ]);

        $resolved = $registry->resolve(
            $this->create_request('RUB')
        );

        $this->assertSame(
            $alfa,
            $resolved
        );
    }

    public function test_unavailable_preferred_provider_is_rejected():
        void {
        $registry =
            new CommercePaymentProviderRegistry([
                $this->create_provider(
                    'stripe',
                    100,
                    false,
                    [
                        'EUR',
                    ]
                ),
            ]);

        $this->expectException(
            CommercePaymentProviderUnavailableException::class
        );

        $registry->resolve(
            $this->create_request(
                'EUR',
                'stripe'
            )
        );
    }

    public function test_missing_provider_is_rejected():
        void {
        $registry =
            new CommercePaymentProviderRegistry();

        $this->expectException(
            CommercePaymentProviderNotFoundException::class
        );

        $registry->resolve(
            $this->create_request('EUR')
        );
    }

    public function test_equal_winning_priorities_are_rejected():
        void {
        $registry =
            new CommercePaymentProviderRegistry([
                $this->create_provider(
                    'stripe',
                    100,
                    true,
                    [
                        'EUR',
                    ]
                ),

                $this->create_provider(
                    'alfa',
                    100,
                    true,
                    [
                        'EUR',
                    ]
                ),
            ]);

        $this->expectException(
            CommercePaymentProviderConflictException::class
        );

        $registry->resolve(
            $this->create_request('EUR')
        );
    }

    public function test_duplicate_provider_key_is_rejected():
        void {
        $registry =
            new CommercePaymentProviderRegistry();

        $registry->register(
            $this->create_provider(
                'stripe',
                100,
                true,
                [
                    'EUR',
                ]
            )
        );

        $this->expectException(
            CommercePaymentProviderConflictException::class
        );

        $registry->register(
            $this->create_provider(
                'stripe',
                50,
                true,
                [
                    'USD',
                ]
            )
        );
    }

    private function create_request(
        string $currency,
        ?string $preferredprovider = null
    ): CommercePaymentRequest {
        return new CommercePaymentRequest(
            'payment:test',
            new CommercePaymentCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePaymentLine(
                    'item:test',
                    'Test item',
                    1,
                    1000,
                    $currency
                ),
            ],
            $currency,
            1000,
            $preferredprovider
        );
    }

    /**
     * Creates a test provider without any external call.
     */
    private function create_provider(
        string $key,
        int $priority,
        bool $available,
        array $currencies
    ): CommercePaymentProvider {
        return new class(
            $key,
            $priority,
            $available,
            $currencies
        ) implements CommercePaymentProvider {

            public function __construct(
                private readonly string $key,
                private readonly int $priority,
                private readonly bool $available,
                private readonly array $currencies
            ) {
            }

            public function get_key(): string {
                return $this->key;
            }

            public function get_priority(): int {
                return $this->priority;
            }

            public function is_available(): bool {
                return $this->available;
            }

            public function get_capabilities():
                CommercePaymentProviderCapabilities {
                return new CommercePaymentProviderCapabilities(
                    $this->currencies,
                    true,
                    true,
                    true
                );
            }

            public function supports(
                CommercePaymentRequest $request
            ): bool {
                return $this
                    ->get_capabilities()
                    ->supports_currency(
                        $request->get_currency()
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
                return CommercePaymentResult::pending(
                    $request->get_reference(),
                    $this->key,
                    $this->key . '-payment-test'
                );
            }

            public function retrieve(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                return CommercePaymentResult::pending(
                    'payment:test',
                    $this->key,
                    $providerpaymentid
                );
            }

            public function cancel(
                string $providerpaymentid,
                CommercePaymentProviderContext $context
            ): CommercePaymentResult {
                return new CommercePaymentResult(
                    'payment:test',
                    $this->key,
                    \local_subscriptions\commerce\payment\result\CommercePaymentStatus::CANCELLED,
                    $providerpaymentid
                );
            }
        };
    }
}