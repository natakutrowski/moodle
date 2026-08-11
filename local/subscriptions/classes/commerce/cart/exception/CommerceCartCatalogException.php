<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\exception;

defined('MOODLE_INTERNAL') || die();

/** Raised when a cart line can no longer be resolved against the catalogue. */
final class CommerceCartCatalogException extends \RuntimeException {
}
