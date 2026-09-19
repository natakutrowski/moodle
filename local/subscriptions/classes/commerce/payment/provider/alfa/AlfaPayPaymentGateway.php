<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Optional Alfa Pay execution port.
 *
 * Kept separate from AlfaPaymentGateway so existing gateway test doubles and
 * the certified card/widget contract do not need to claim Alfa Pay support.
 */
interface AlfaPayPaymentGateway {
    public function is_alfa_pay_configured(): bool;

    public function register_alfa_pay(
        AlfaGatewayRequest $request
    ): AlfaGatewayResponse;
}
