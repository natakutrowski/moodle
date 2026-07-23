<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;

/**
 * Secondary action executed after access has been granted.
 */
interface CommercePostFulfillmentAction {

    public function get_key(): string;

    public function supports(
        CommerceFulfillmentResult $result
    ): bool;

    public function execute(
        CommerceFulfillmentResult $result,
        CommerceFulfillmentContext $context
    ): CommercePostFulfillmentActionResult;
}
