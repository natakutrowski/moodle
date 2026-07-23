<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Port isolating Commerce from the current Alfa integration.
 */
interface AlfaPaymentGateway {

    public function is_configured(): bool;

    public function register(
        AlfaGatewayRequest $request
    ): AlfaGatewayResponse;

    public function retrieve(
        string $orderid
    ): AlfaGatewayResponse;

    public function cancel(
        string $orderid
    ): AlfaGatewayResponse;
}