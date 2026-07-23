<?php

namespace local_subscriptions\commerce\fulfillment\subscription;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;

/**
 * Legacy boundary used to grant subscription access.
 */
interface SubscriptionFulfillmentGateway {

    public function find_by_transaction(
        string $transactionid
    ): ?\stdClass;

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): array;
}
