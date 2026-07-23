<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Port isolating Commerce from the current Stripe implementation.
 */
interface StripePaymentGateway {

    public function is_configured(): bool;

    public function create_checkout_session(
        StripeGatewayRequest $request
    ): StripeGatewayResponse;

    public function retrieve(
        string $paymentid
    ): StripeGatewayResponse;

    public function cancel(
        string $paymentid
    ): StripeGatewayResponse;
}