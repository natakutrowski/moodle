<?php

namespace local_subscriptions\commerce\fulfillment\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;

/**
 * Legacy boundary used to grant digital download access.
 */
interface DigitalFulfillmentGateway {

    public function find_payment_request(int $paymentrequestid): ?\stdClass;

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): \stdClass;
}
