<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\paypal\PayPalCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOrderRequest;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOrderResponse;
use local_subscriptions\commerce\payment\provider\paypal\PayPalPaymentGateway;
use local_subscriptions\commerce\payment\provider\paypal\PayPalRefundResponse;
use local_subscriptions\commerce\payment\provider\paypal\PayPalPaymentProviderConfiguration;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f2_paypal_orders_provider_test extends advanced_testcase {
    public function test_create_order_maps_to_redirect_action(): void {
        $gateway = new class implements PayPalPaymentGateway {
            public ?PayPalOrderRequest $created = null;

            public function is_configured(): bool {
                return true;
            }

            public function create_order(
                PayPalOrderRequest $request
            ): PayPalOrderResponse {
                $this->created = $request;

                return new PayPalOrderResponse(
                    'ORDER-123',
                    'CREATED',
                    'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123'
                );
            }

            public function retrieve_order(
                string $orderid
            ): PayPalOrderResponse {
                return new PayPalOrderResponse(
                    $orderid,
                    'APPROVED'
                );
            }

            public function capture_order(
                string $orderid,
                string $idempotencykey
            ): PayPalOrderResponse {
                return new PayPalOrderResponse(
                    $orderid,
                    'COMPLETED',
                    null,
                    'CAPTURE-123'
                );
            }
        
            public function refund_capture(
                string $captureid,
                int $amountminor,
                string $currency,
                string $idempotencykey,
                ?string $note = null
            ): PayPalRefundResponse {
                throw new \coding_exception('Not used by this test.');
            }


            public function retrieve_capture(
                string $captureid
            ): array {
                return [];
            }


            public function retrieve_refund(
                string $refundid
            ): PayPalRefundResponse {
                throw new \coding_exception('Not used by this test.');
            }

        };

        $provider = new PayPalCommercePaymentProvider(
            $gateway,
            new PayPalPaymentProviderConfiguration(false)
        );

        $request = $this->request();

        $result = $provider->initialize(
            $request,
            new CommercePaymentProviderContext(
                'paypal-f2-create',
                false
            )
        );

        $this->assertSame(
            CommercePaymentStatus::REQUIRES_ACTION,
            $result->get_status()
        );
        $this->assertSame(
            'ORDER-123',
            $result->get_provider_payment_id()
        );
        $this->assertTrue(
            $result->get_action()?->is_redirect()
        );
        $this->assertSame(
            'paypal-f2-create',
            $gateway->created?->get_idempotency_key()
        );
    }

    public function test_capture_completed_order_maps_to_success(): void {
        $gateway = new class implements PayPalPaymentGateway {
            public string $capturekey = '';

            public function is_configured(): bool {
                return true;
            }

            public function create_order(
                PayPalOrderRequest $request
            ): PayPalOrderResponse {
                return new PayPalOrderResponse(
                    'ORDER-123',
                    'CREATED',
                    'https://sandbox.paypal.com/approve'
                );
            }

            public function retrieve_order(
                string $orderid
            ): PayPalOrderResponse {
                return new PayPalOrderResponse(
                    $orderid,
                    'APPROVED'
                );
            }

            public function capture_order(
                string $orderid,
                string $idempotencykey
            ): PayPalOrderResponse {
                $this->capturekey = $idempotencykey;

                return new PayPalOrderResponse(
                    $orderid,
                    'COMPLETED',
                    null,
                    'CAPTURE-456'
                );
            }
        
            public function refund_capture(
                string $captureid,
                int $amountminor,
                string $currency,
                string $idempotencykey,
                ?string $note = null
            ): PayPalRefundResponse {
                throw new \coding_exception('Not used by this test.');
            }


            public function retrieve_capture(
                string $captureid
            ): array {
                return [];
            }


            public function retrieve_refund(
                string $refundid
            ): PayPalRefundResponse {
                throw new \coding_exception('Not used by this test.');
            }

        };

        $provider = new PayPalCommercePaymentProvider(
            $gateway,
            new PayPalPaymentProviderConfiguration(false)
        );

        $result = $provider->capture(
            'ORDER-123',
            new CommercePaymentProviderContext(
                'paypal-f2-return',
                false
            ),
            'PAYMENT-123'
        );

        $this->assertSame(
            CommercePaymentStatus::SUCCEEDED,
            $result->get_status()
        );
        $this->assertSame(
            'CAPTURE-456',
            $result->get_metadata_value(
                'paypal_capture_id'
            )
        );
        $this->assertSame(
            'paypal-f2-return-capture',
            $gateway->capturekey
        );
    }

    private function request(): CommercePaymentRequest {
        return new CommercePaymentRequest(
            'PAYMENT-123',
            new CommercePaymentCustomer(
                42,
                'buyer@example.test',
                'Buyer',
                'Sandbox'
            ),
            [
                new CommercePaymentLine(
                    'SKU.TEST',
                    'CampusFR test product',
                    1,
                    3000,
                    'EUR'
                ),
            ],
            'EUR',
            3000,
            'paypal',
            'https://campus.example.test/paypal/return',
            'https://campus.example.test/paypal/cancel',
            [],
            null,
            'paypal'
        );
    }
}
