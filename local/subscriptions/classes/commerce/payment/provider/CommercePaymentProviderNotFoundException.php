<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when no Commerce payment provider matches a request.
 */
final class CommercePaymentProviderNotFoundException
    extends CommercePaymentProviderException {
}