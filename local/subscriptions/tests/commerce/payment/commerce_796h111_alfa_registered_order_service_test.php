<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayRequest;
use local_subscriptions\commerce\payment\provider\alfa\AlfaGatewayResponse;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPaymentGateway;
use local_subscriptions\commerce\payment\provider\alfa\AlfaRegisteredOrderService;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;

/**
 * H11.1 contract for server-side Alfa order registration used by fast methods.
 *
 * @covers \local_subscriptions\commerce\payment\provider\alfa\AlfaRegisteredOrder
 * @covers \local_subscriptions\commerce\payment\provider\alfa\AlfaRegisteredOrderService
 */
final class commerce_796h111_alfa_registered_order_service_test extends advanced_testcase {

    public function test_service_registers_order_without_using_widget_path(): void {
        $request = $this->create_request();

        $gateway = $this->createMock(AlfaPaymentGateway::class);
        $gateway->expects($this->once())
            ->method('is_configured')
            ->willReturn(true);
        $gateway->expects($this->once())
            ->method('register')
            ->with($this->identicalTo($request))
            ->willReturn(new AlfaGatewayResponse(
                '05adca5e-718a-7e02-a5c9-7d4302682730',
                AlfaGatewayResponse::STATUS_REGISTERED,
                'https://securepayecom.example/payment/form',
                null,
                null,
                [
                    'alfa_order_id' => '05adca5e-718a-7e02-a5c9-7d4302682730',
                    'commerce_payment_id' => '620',
                ]
            ));
        $gateway->expects($this->never())
            ->method('prepare_widget');

        $order = (new AlfaRegisteredOrderService($gateway))
            ->register($request);

        self::assertSame(
            '05adca5e-718a-7e02-a5c9-7d4302682730',
            $order->get_order_id()
        );
        self::assertSame(
            'https://securepayecom.example/payment/form',
            $order->get_form_url()
        );
        self::assertSame(
            '620',
            $order->get_metadata_value('commerce_payment_id')
        );
    }

    public function test_service_fails_before_registration_when_gateway_is_not_configured(): void {
        $gateway = $this->createMock(AlfaPaymentGateway::class);
        $gateway->expects($this->once())
            ->method('is_configured')
            ->willReturn(false);
        $gateway->expects($this->never())
            ->method('register');

        $this->expectException(CommercePaymentProviderException::class);

        (new AlfaRegisteredOrderService($gateway))
            ->register($this->create_request());
    }

    private function create_request(): AlfaGatewayRequest {
        return new AlfaGatewayRequest(
            'commerce:alfa:h11:1',
            30000,
            'RUB',
            'Test produit digital 1',
            'student@example.com',
            'https://example.test/payment/return',
            'https://example.test/cart',
            'commerce:alfa:h11:1:idempotency',
            [
                'commerce_reference' => 'commerce-506-1',
                'commerce_payment_id' => '620',
                'commerce_purchase_uuid' => 'b54b7832bfa76bf8e7783e340fb556c0',
            ]
        );
    }
}
