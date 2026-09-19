<?php

namespace local_subscriptions\payment\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\CheckoutInitResult;

/**
 * Optional Alfa fast-payment extensions exposed by the native gateway.
 */
interface AlfaFastPaymentGatewayInterface {
    public function create_alfapay_session(
        \stdClass $payment_request,
        array $options = []
    ): CheckoutInitResult;

    public function create_sbp_session(
        \stdClass $payment_request,
        array $options = []
    ): AlfaSbpInitResult;
}
