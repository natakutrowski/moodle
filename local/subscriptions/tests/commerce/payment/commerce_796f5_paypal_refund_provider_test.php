<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\paypal\PayPalCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOrderRequest;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOrderResponse;
use local_subscriptions\commerce\payment\provider\paypal\PayPalPaymentGateway;
use local_subscriptions\commerce\payment\provider\paypal\PayPalPaymentProviderConfiguration;
use local_subscriptions\commerce\payment\provider\paypal\PayPalRefundResponse;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRequest;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f5_paypal_refund_provider_test extends advanced_testcase {
    public function test_paypal_declares_and_implements_refund_contracts(): void {
        $provider = $this->provider();

        $this->assertInstanceOf(
            CommerceRefundCapablePaymentProvider::class,
            $provider
        );
        $this->assertInstanceOf(
            CommerceRefundHistoryCapablePaymentProvider::class,
            $provider
        );
        $this->assertTrue(
            $provider->get_capabilities()->supports_refunds()
        );
    }

    public function test_paypal_maps_completed_refund_to_commerce_result(): void {
        $provider = $this->provider();

        $result = $provider->refund(
            new CommercePaymentRefundRequest(
                'PAYMENT-42',
                'CAPTURE-42',
                'EUR',
                100,
                'requested_by_customer'
            ),
            new CommercePaymentProviderContext(
                'paypal-refund-42',
                false
            )
        );

        $this->assertSame(
            CommercePaymentRefundResult::STATUS_SUCCEEDED,
            $result->get_status()
        );
        $this->assertSame(
            'REFUND-42',
            $result->get_provider_refund_id()
        );
        $this->assertSame(100, $result->get_amount_minor());
        $this->assertSame('EUR', $result->get_currency());
    }

    private function provider(): PayPalCommercePaymentProvider {
        $gateway = new class implements PayPalPaymentGateway {
            public function is_configured(): bool { return true; }
            public function create_order(PayPalOrderRequest $request): PayPalOrderResponse {
                throw new \coding_exception('Not used.');
            }
            public function retrieve_order(string $orderid): PayPalOrderResponse {
                throw new \coding_exception('Not used.');
            }
            public function capture_order(string $orderid, string $idempotencykey): PayPalOrderResponse {
                throw new \coding_exception('Not used.');
            }
            public function refund_capture(
                string $captureid,
                int $amountminor,
                string $currency,
                string $idempotencykey,
                ?string $note = null
            ): PayPalRefundResponse {
                return new PayPalRefundResponse(
                    'REFUND-42',
                    'COMPLETED',
                    $currency,
                    $amountminor,
                    ['captureid' => $captureid]
                );
            }
            public function retrieve_capture(string $captureid): array {
                return [];
            }
        
            public function retrieve_refund(
                string $refundid
            ): PayPalRefundResponse {
                throw new \coding_exception('Not used by this test.');
            }

        };

        return new PayPalCommercePaymentProvider(
            $gateway,
            new PayPalPaymentProviderConfiguration(true)
        );
    }
}
