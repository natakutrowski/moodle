<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

/** Raised when no Native handler exists for a grant type. */
final class CommerceNativeFulfillmentHandlerNotFoundException extends \RuntimeException {
    public function __construct(string $type) {
        parent::__construct(
            sprintf('No Native Commerce fulfillment handler is registered for grant type "%s".', $type)
        );
    }
}
