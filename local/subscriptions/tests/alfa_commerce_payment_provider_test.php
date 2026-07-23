<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayRequest;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayResponse;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentGateway;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentProviderConfiguration;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;

/**
 * Tests the Alfa Commerce payment adapter.
 *
 * @covers \local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider
 */
final class alfa_commerce_payment_provider_test
    extends advanced_testcase {

    public function test_registered_order_maps_to_redirect():
        void {
        $provider = new AlfaCommercePaymentProvider(
            $this->create_gateway(),
            new AlfaPaymentProviderConfiguration(
                true,
                ['RUB'],
                90
            )
        );

        $result = $provider->initialize(
            $this->create_request(),
            new CommercePaymentProviderContext(
                'alfa:test:1',
                false
            )
        );

        $this->assertSame(
            CommercePaymentStatus::REQUIRES_ACTION,
            $result->get_status()
        );

        $this->assertSame(
            'alfa-order-123',
            $result->get_provider_payment_id()
        );

        $this->assertTrue(
            $result->get_action()->is_redirect()
        );
    }

    public function test_paid_order_maps_to_success():
        void {
        $provider = new AlfaCommercePaymentProvider(
            $this->create_gateway(),
            new AlfaPaymentProviderConfiguration(
                true,
                ['RUB']
            )
        );

        $result = $provider->retrieve(
            'alfa-order-123',
            new CommercePaymentProviderContext(
                'alfa:test:retrieve',
                false
            )
        );

        $this->assertSame(
            CommercePaymentStatus::SUCCEEDED,
            $result->get_status()
        );

        $this->assertTrue(
            $result->is_successful()
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
                    1200000,
                    'RUB'
                ),
            ],
            'RUB',
            1200000,
            'alfa',
            'https://example.com/success',
            'https://example.com/failure'
        );
    }

    private function create_gateway():
        AlfaPaymentGateway {
        return new class implements AlfaPaymentGateway {

            public function is_configured(): bool {
                return true;
            }

            public function register(
                AlfaGatewayRequest $request
            ): AlfaGatewayResponse {
                return new AlfaGatewayResponse(
                    'alfa-order-123',
                    AlfaGatewayResponse::STATUS_REGISTERED,
                    'https://alfa.example.test/payment',
                    null,
                    null,
                    [
                        'commerce_reference' =>
                            'purchase:test',
                    ]
                );
            }

            public function retrieve(
                string $orderid
            ): AlfaGatewayResponse {
                return new AlfaGatewayResponse(
                    $orderid,
                    AlfaGatewayResponse::STATUS_PAID,
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
                string $orderid
            ): AlfaGatewayResponse {
                return new AlfaGatewayResponse(
                    $orderid,
                    AlfaGatewayResponse::STATUS_CANCELLED,
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
            ) implements AlfaPaymentGateway {

                public function __construct(
                    private mixed &$capturedmetadata
                ) {
                }

                public function is_configured(): bool {
                    return true;
                }

                public function register(
                    AlfaGatewayRequest $request
                ): AlfaGatewayResponse {
                    $this->capturedmetadata =
                        $request->get_metadata();

                    return new AlfaGatewayResponse(
                        'alfa-order-metadata-test',
                        AlfaGatewayResponse::STATUS_REGISTERED,
                        'https://alfa.example.test/form',
                        null,
                        null,
                        [
                            'commerce_reference' =>
                                $request->get_metadata()[
                                    'commerce_reference'
                                ],
                        ]
                    );
                }

                public function retrieve(
                    string $orderid
                ): AlfaGatewayResponse {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }

                public function cancel(
                    string $orderid
                ): AlfaGatewayResponse {
                    throw new \coding_exception(
                        'Not used by this test.'
                    );
                }
            };

        $provider =
            new AlfaCommercePaymentProvider(
                $gateway,
                new AlfaPaymentProviderConfiguration(
                    true,
                    [
                        'RUB',
                    ]
                )
            );

        $request =
            new CommercePaymentRequest(
                'purchase:alfa:legacy',
                new CommercePaymentCustomer(
                    96,
                    'student@example.com'
                ),
                [
                    new CommercePaymentLine(
                        'subscription-plan:14',
                        'CampusFR A1',
                        1,
                        1200000,
                        'RUB'
                    ),
                ],
                'RUB',
                1200000,
                'alfa',
                'https://example.test/alfa-return',
                'https://example.test/alfa-fail',
                [
                    'legacy_payment_request_id' =>
                        123,

                    'legacy_payment_request_table' =>
                        'subscription_payment_request',

                    'legacy_payment_context' =>
                        'subscription',

                    'legacy_order_number_prefix' =>
                        'sub',

                    'legacy_operation' =>
                        \local_subscriptions\constants\Operation::PURCHASE_NEW,

                    'legacy_language' =>
                        'ru',

                    'legacy_plan_id' =>
                        14,
                ]
            );

        $provider->initialize(
            $request,
            new CommercePaymentProviderContext(
                'alfa:metadata:test',
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
            'sub',
            $capturedmetadata[
                'legacy_order_number_prefix'
            ]
        );

        $this->assertSame(
            96,
            $capturedmetadata['userid']
        );

        $this->assertSame(
            'purchase:alfa:legacy',
            $capturedmetadata[
                'commerce_reference'
            ]
        );
    }

public function test_initialize_preserves_bridge_exception():
    void {
    $gateway =
        new class implements AlfaPaymentGateway {

            public function is_configured(): bool {
                return true;
            }

            public function register(
                AlfaGatewayRequest $request
            ): AlfaGatewayResponse {
                throw new CommercePaymentProviderException(
                    'Legacy mapping failed.',
                    'alfa',
                    'legacy_payment_currency_mismatch'
                );
            }

            public function retrieve(
                string $orderid
            ): AlfaGatewayResponse {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }

            public function cancel(
                string $orderid
            ): AlfaGatewayResponse {
                throw new \coding_exception(
                    'Not used by this test.'
                );
            }
        };

    $provider =
        new AlfaCommercePaymentProvider(
            $gateway,
            new AlfaPaymentProviderConfiguration(
                true,
                [
                    'RUB',
                ]
            )
        );

    try {
        $provider->initialize(
            $this->create_request(),
            new CommercePaymentProviderContext(
                'alfa:bridge:error:test',
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
            'legacy_payment_currency_mismatch',
            $exception->get_provider_code()
        );
    }
}

}