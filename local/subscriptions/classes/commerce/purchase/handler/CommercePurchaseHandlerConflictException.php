<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when multiple PurchaseHandlers claim the same Commerce item.
 */
final class CommercePurchaseHandlerConflictException
    extends \RuntimeException {
}