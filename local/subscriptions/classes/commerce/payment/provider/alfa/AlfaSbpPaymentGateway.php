<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Optional SBP execution port for the Alfa Commerce provider.
 */
interface AlfaSbpPaymentGateway {
    public function is_sbp_configured(): bool;

    public function register_sbp(
        AlfaGatewayRequest $request
    ): AlfaGatewayResponse;
}
