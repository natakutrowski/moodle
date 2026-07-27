<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\application;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Applies one concrete Native Commerce entitlement type.
 */
interface CommerceEntitlementApplicationHandler {
    public function get_type(): string;

    public function apply(
        CommerceEntitlementGrant $grant,
        CommerceEntitlementApplicationContext $context
    ): array;
}
