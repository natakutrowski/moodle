<?php

namespace local_subscriptions\commerce\catalog\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/** Immutable result of the Native catalogue-to-payment preparation pipeline. */
final class CommerceCatalogPaymentPipelineResult {
    public function __construct(
        private readonly CommercePurchasePreparation $preparation,
        private readonly CommercePaymentRequest $paymentrequest
    ) {
    }

    public function get_preparation(): CommercePurchasePreparation {
        return $this->preparation;
    }

    public function get_payment_request(): CommercePaymentRequest {
        return $this->paymentrequest;
    }
}
