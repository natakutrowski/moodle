<?php

namespace local_subscriptions\commerce\payment\result;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when a provider returns an inconsistent Commerce payment result.
 */
final class CommercePaymentResultException
    extends \RuntimeException {
}