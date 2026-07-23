<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayRequest;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayResponse;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayRequest;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayResponse;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentGateway;

/**
 * Tests the Commerce provider registry factory.
 *
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory
 */
final class commerce_payment_provider_registry_factory_test
    extends advanced_testcase {

    public function test_factory_registers_stripe_and_alfa():
        void {
        global $DB;

        $this->resetAfterTest();

        $registry =
            CommercePaymentProviderRegistryFactory::create(
                $DB,
                $this->create_stripe_gateway(true),
                $this->create_alfa_gateway(true)
            );

        $this->assertSame(
            [
                StripeCommercePaymentProvider::KEY,
                AlfaCommercePaymentProvider::KEY,
            ],
            $registry->keys()
        );

        $this->assertInstanceOf(
            StripeCommercePaymentProvider::class,
            $registry->get(
                StripeCommercePaymentProvider::KEY
            )
        );

        $this->assertInstanceOf(
            AlfaCommercePaymentProvider::class,
            $registry->get(
                AlfaCommercePaymentProvider::KEY
            )
        );
    }

    public function test_unconfigured_providers_remain_registered():
        void {
        global $DB;

        $this->resetAfterTest();

        $registry =
            CommercePaymentProviderRegistryFactory::create(
                $DB,
                $this->create_stripe_gateway(false),
                $this->create_alfa_gateway(false)
            );

        $this->assertTrue(
            $registry->has(
                StripeCommercePaymentProvider::KEY
            )
        );

        $this->assertTrue(
            $registry->has(
                AlfaCommercePaymentProvider::KEY
            )
        );

        $this->assertFalse(
            $registry
                ->get(
                    StripeCommercePaymentProvider::KEY
                )
                ->is_available()
        );

        $this->assertFalse(
            $registry
                ->get(
                    AlfaCommercePaymentProvider::KEY
                )
                ->is_available()
        );

        $this->assertSame(
            [],
            $registry->available()
        );
    }

    public function test_configured_providers_are_available():
        void {
        global $DB;

        $this->resetAfterTest();

        $registry =
            CommercePaymentProviderRegistryFactory::create(
                $DB,
                $this->create_stripe_gateway(true),
                $this->create_alfa_gateway(true)
            );

        $this->assertCount(
            2,
            $registry->available()
        );
    }

    private function create_stripe_gateway(
        bool $configured
    ): StripePaymentGateway {
        return new class(
            $configured
        ) implements StripePaymentGateway {

            public function __construct(
                private readonly bool $configured
            ) {
            }

            public function is_configured(): bool {
                return $this->configured;
            }

            public function create_checkout_session(
                StripeGatewayRequest $request
            ): StripeGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }

            public function retrieve(
                string $paymentid
            ): StripeGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }

            public function cancel(
                string $paymentid
            ): StripeGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }
        };
    }

    private function create_alfa_gateway(
        bool $configured
    ): AlfaPaymentGateway {
        return new class(
            $configured
        ) implements AlfaPaymentGateway {

            public function __construct(
                private readonly bool $configured
            ) {
            }

            public function is_configured(): bool {
                return $this->configured;
            }

            public function register(
                AlfaGatewayRequest $request
            ): AlfaGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }

            public function retrieve(
                string $orderid
            ): AlfaGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }

            public function cancel(
                string $orderid
            ): AlfaGatewayResponse {
                throw new \coding_exception(
                    'Not used by this factory test.'
                );
            }
        };
    }
}