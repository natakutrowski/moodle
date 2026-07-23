<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when provider resolution is ambiguous.
 */
final class CommercePaymentProviderConflictException
    extends CommercePaymentProviderException {
}