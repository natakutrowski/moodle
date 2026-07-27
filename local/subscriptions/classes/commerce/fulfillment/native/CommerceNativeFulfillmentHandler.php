<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Executes one concrete Native Commerce entitlement type directly in Moodle.
 *
 * Implementations must not delegate to Legacy fulfillment gateways.
 */
interface CommerceNativeFulfillmentHandler {
    public function get_grant_type(): string;

    public function fulfill(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult;
}
