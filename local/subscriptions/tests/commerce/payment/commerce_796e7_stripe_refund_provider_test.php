<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayRequest;
use local_subscriptions\commerce\payment\provider\stripe\StripeGatewayResponse;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentGateway;
use local_subscriptions\commerce\payment\provider\stripe\StripePaymentProviderConfiguration;
use local_subscriptions\commerce\payment\provider\stripe\StripeRefundRequest;
use local_subscriptions\commerce\payment\provider\stripe\StripeRefundResponse;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRequest;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e7_stripe_refund_provider_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('stripe_enabled', 1, 'local_subscriptions');
    }

    public function test_stripe_provider_declares_certified_refund_capability(): void {
        $provider = $this->provider();

        $this->assertInstanceOf(
            CommerceRefundCapablePaymentProvider::class,
            $provider
        );
        $this->assertTrue(
            $provider->get_capabilities()->supports_refunds()
        );
    }

    public function test_stripe_provider_maps_gateway_refund_to_commerce_result(): void {
        $provider = $this->provider();

        $result = $provider->refund(
            new CommercePaymentRefundRequest(
                'PAY.E7',
                'cs_test_123',
                'EUR',
                1200,
                'requested_by_customer',
                ['purchase_reference' => 'PUR.E7']
            ),
            new CommercePaymentProviderContext(
                'refund-e7-1',
                false
            )
        );

        $this->assertSame('stripe', $result->get_provider_key());
        $this->assertSame('re_test_123', $result->get_provider_refund_id());
        $this->assertSame(
            CommercePaymentRefundResult::STATUS_SUCCEEDED,
            $result->get_status()
        );
        $this->assertSame('EUR', $result->get_currency());
        $this->assertSame(1200, $result->get_amount_minor());
    }

    private function provider(): StripeCommercePaymentProvider {
        $gateway = new class implements StripePaymentGateway {
            public function is_configured(): bool { return true; }

            public function create_checkout_session(
                StripeGatewayRequest $request
            ): StripeGatewayResponse {
                throw new \coding_exception('Not used.');
            }

            public function retrieve(string $paymentid): StripeGatewayResponse {
                throw new \coding_exception('Not used.');
            }

            public function cancel(string $paymentid): StripeGatewayResponse {
                throw new \coding_exception('Not used.');
            }

            public function refund(
                StripeRefundRequest $request
            ): StripeRefundResponse {
                return new StripeRefundResponse(
                    're_test_123',
                    'succeeded',
                    $request->get_currency(),
                    $request->get_amount_minor(),
                    ['payment_intent' => 'pi_test_123']
                );
            }
        
            public function create_payment_intent(
                StripeGatewayRequest $request
            ): StripeGatewayResponse {
                throw new \coding_exception('Not used by this test.');
            }


            public function list_refunds(
                string $providerpaymentid,
                string $currency
            ): array {
                return [];
            }

        };

        return new StripeCommercePaymentProvider(
            $gateway,
            new StripePaymentProviderConfiguration(true)
        );
    }
}
