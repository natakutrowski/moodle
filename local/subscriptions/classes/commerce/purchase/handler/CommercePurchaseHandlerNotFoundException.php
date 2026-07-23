<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when no PurchaseHandler supports a Commerce item.
 */
final class CommercePurchaseHandlerNotFoundException
    extends \RuntimeException {
}