<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Executes one family of post-payment Commerce operations.
 */
interface CommerceFulfillmentHandler {

    public function get_key(): string;

    public function supports(
        CommerceFulfillmentOperation $operation
    ): bool;

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): CommerceFulfillmentResult;
}
