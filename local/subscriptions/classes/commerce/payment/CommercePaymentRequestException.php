<?php

namespace local_subscriptions\commerce\payment;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a Commerce payment request cannot be created.
 */
final class CommercePaymentRequestException
    extends \RuntimeException {
}