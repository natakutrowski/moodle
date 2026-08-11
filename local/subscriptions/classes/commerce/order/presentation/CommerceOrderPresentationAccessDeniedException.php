<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Raised when a customer attempts to read another customer's order. */
final class CommerceOrderPresentationAccessDeniedException extends \RuntimeException {
}
