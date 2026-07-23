<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when no fulfillment handler can execute an operation.
 */
final class CommerceFulfillmentHandlerNotFoundException
    extends \RuntimeException {
}