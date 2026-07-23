<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when an explicitly selected provider is unavailable.
 */
final class CommercePaymentProviderUnavailableException
    extends CommercePaymentProviderException {
}