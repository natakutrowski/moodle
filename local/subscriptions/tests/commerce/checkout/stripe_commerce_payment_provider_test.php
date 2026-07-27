<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayRequest;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayResponse;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentProviderConfiguration;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;

/**
 * Tests the Stripe Commerce payment adapter.
 *
 * @covers \local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider
 */
final class stripe_commerce_payment_provider_test
    extends advanced_testcase {

    public function test_checkout_session_maps_to_redirect():
        void {
        $provider = new StripeCommercePaymentProvider(
            $this->create_gateway(),
            new StripePaymentProviderConfiguration(
                true,
                ['EUR'],
                100
            )
        );

        $result = $provider->initialize(
            $this->create_request(),
            new CommercePaymentProviderContext(
                'stripe:test:1',
                false
            )
        );

        $this->assertSame(
            CommercePaymentStatus::REQUIRES_ACTION,
            $result->get_status()
        );

        $this->assertSame(
            'stripe',
            $result->get_provider_key()
        );

        $this->assertTrue(
            $result->get_action()->is_redirect()
        );
    }

    public function test_unsupported_currency_is_rejected():
        void {
        $provider = new StripeCommercePaymentProvider(
            $this->create_gateway(),
            new StripePaymentProviderConfiguration(
                true,
                ['EUR']
            )
        );

        $request = new CommercePaymentRequest(
            'purchase:rub',
            new CommercePaymentCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePaymentLine(
                    'item:1',
                    'Test',
                    1,
                    10000,
                    'RUB'
                ),
            ],
            'RUB',
            10000,
            'stripe',
            'https://example.com/success',
            'https://example.com/cancel'
        );

        $this->assertFalse(
            $provider->supports($request)
        );
    }

    private function create_request():
        CommercePaymentRequest {
        return new CommercePaymentRequest(
            'purchase:test',
            new CommercePaymentCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePaymentLine(
                    'subscription-plan:14',
                    'CampusFR A1',
                    1,
                    12000,
                    'EUR'
                ),
            ],
            'EUR',
            12000,
            'stripe',
            'https://example.com/success',
            'https://example.com/cancel'
        );
    }

    private function create_gateway():
        StripePaymentGateway {
        return new class implements StripePaymentGateway {

            public function is_configured(): bool {
                return true;
            }

            public function create_checkout_session(
                StripeGatewayRequest $request
            ): StripeGatewayResponse {
                return new StripeGatewayResponse(
                    'cs_test_123',
                    StripeGatewayResponse::STATUS_OPEN,
                    'https://checkout.stripe.test/session',
                    null,
                    null,
                    [
                        'commerce_reference' =>
                            $request->get_reference(),
                    ]
                );
            }

            public function retrieve(
                string $paymentid
            ): StripeGatewayResponse {
                return new StripeGatewayResponse(
                    $paymentid,
                    StripeGatewayResponse::STATUS_PAID,
                    null,
                    null,
                    null,
                    [
                        'commerce_reference' =>
                            'purchase:test',
                    ]
                );
            }

            public function cancel(
                string $paymentid
            ): StripeGatewayResponse {
                return new StripeGatewayResponse(
                    $paymentid,
                    StripeGatewayResponse::STATUS_EXPIRED,
                    null,
                    null,
                    null,
                    [
                        'commerce_reference' =>
                            'purchase:test',
                    ]
                );
            }
        };
    }

    public function test_legacy_metadata_is_forwarded_to_gateway():
        void {
        $capturedmetadata = null;

        $gateway =
            new class(
                $capturedmetadata
            ) implements StripePaymentGateway {

                public function __construct(
                    private mixed &$capturedmetadata
                ) {
                }

                public function is_configured(): bool {
                    return true;
                }

                public function create_checkout_session(
                    StripeGatewayRequest $request
                ): StripeGatewayResponse {
                    $this->capturedmetadata =
                        $request->get_metadata();

                    return new StripeGatewayResponse(
                        'cs_test_legacy_metadata',
                        StripeGatewayResponse::STATUS_OPEN,
                        'https://checkout.stripe.test/session',
                        null,
                        null,
                        [
                            'commerce_reference' =>
                                $request->get_reference(),
                        ]
                    );
                }

                public function retrieve(
                    string $paymentid
                ): StripeGatewayResponse {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }

                public function cancel(
                    string $paymentid
                ): StripeGatewayResponse {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }
            };

        $provider =
            new StripeCommercePaymentProvider(
                $gateway,
                new StripePaymentProviderConfiguration(
                    true,
                    [
                        'EUR',
                    ]
                )
            );

        $request =
            new CommercePaymentRequest(
                'purchase:test:legacy',
                new CommercePaymentCustomer(
                    96,
                    'student@example.com'
                ),
                [
                    new CommercePaymentLine(
                        'subscription-plan:14',
                        'CampusFR A1',
                        1,
                        12000,
                        'EUR'
                    ),
                ],
                'EUR',
                12000,
                'stripe',
                'https://example.com/success',
                'https://example.com/cancel',
                [
                    'legacy_payment_request_id' =>
                        123,

                    'legacy_payment_request_table' =>
                        'subscription_payment_request',

                    'legacy_payment_context' =>
                        'subscription',

                    'legacy_order_number_prefix' =>
                        'sub',

                    'legacy_mode' =>
                        'payment',

                    'legacy_plan_id' =>
                        14,
                ]
            );

        $provider->initialize(
            $request,
            new CommercePaymentProviderContext(
                'stripe:metadata:test',
                false
            )
        );

        $this->assertIsArray(
            $capturedmetadata
        );

        $this->assertSame(
            123,
            $capturedmetadata[
                'legacy_payment_request_id'
            ]
        );

        $this->assertSame(
            'subscription_payment_request',
            $capturedmetadata[
                'legacy_payment_request_table'
            ]
        );

        $this->assertSame(
            'subscription',
            $capturedmetadata[
                'legacy_payment_context'
            ]
        );

        $this->assertSame(
            96,
            $capturedmetadata[
                'userid'
            ]
        );

        $this->assertSame(
            'purchase:test:legacy',
            $capturedmetadata[
                'commerce_reference'
            ]
        );
    }

public function test_initialize_preserves_bridge_exception():
    void {
    $gateway =
        new class implements StripePaymentGateway {

            public function is_configured(): bool {
                return true;
            }

            public function create_checkout_session(
                StripeGatewayRequest $request
            ): StripeGatewayResponse {
                throw new CommercePaymentProviderException(
                    'Legacy mapping failed.',
                    'stripe',
                    'legacy_payment_amount_mismatch'
                );
            }

            public function retrieve(
                string $paymentid
            ): StripeGatewayResponse {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }

            public function cancel(
                string $paymentid
            ): StripeGatewayResponse {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }
        };

    $provider =
        new StripeCommercePaymentProvider(
            $gateway,
            new StripePaymentProviderConfiguration(
                true,
                [
                    'EUR',
                ]
            )
        );

    try {
        $provider->initialize(
            $this->create_request(),
            new CommercePaymentProviderContext(
                'stripe:bridge:error:test',
                false
            )
        );

        $this->fail(
            'The bridge exception should have been preserved.'
        );
    } catch (
        CommercePaymentProviderException $exception
    ) {
        $this->assertSame(
            'legacy_payment_amount_mismatch',
            $exception->get_provider_code()
        );
    }
}

}