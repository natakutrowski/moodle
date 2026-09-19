<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\payment\Provider;

/**
 * Registers an Alfa order for H11 direct/fast payment methods.
 *
 * This intentionally reuses the certified Alfa gateway instead of creating a
 * second HTTP client. The underlying LegacyAlfaPaymentGateway delegates to the
 * existing AlfaGateway::create_checkout_session(), which performs register.do,
 * keeps API credentials server-side and persists Alfa's orderId/formUrl.
 */
final class AlfaRegisteredOrderService {

    public function __construct(
        private readonly AlfaPaymentGateway $gateway
    ) {
    }

    public function register(
        AlfaGatewayRequest $request
    ): AlfaRegisteredOrder {
        if (!$this->gateway->is_configured()) {
            throw new CommercePaymentProviderException(
                'The Alfa gateway is not configured for registered orders.',
                Provider::ALFA,
                'alfa_registered_order_not_configured'
            );
        }

        $response = $this->gateway->register($request);

        $status = $response->get_status();
        if (!in_array(
            $status,
            [
                AlfaGatewayResponse::STATUS_REGISTERED,
                AlfaGatewayResponse::STATUS_PENDING,
            ],
            true
        )) {
            throw new CommercePaymentProviderException(
                'Alfa did not return a usable registered order.',
                Provider::ALFA,
                'alfa_registered_order_invalid_status',
                ['status' => $status]
            );
        }

        return new AlfaRegisteredOrder(
            $response->get_order_id(),
            $response->get_form_url(),
            $response->get_metadata()
        );
    }
}
