<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Persists current state and immutable attempt history for Native fulfillment. */
interface CommerceNativeFulfillmentPersistenceRepository {
    public function find_state(string $grantreference): ?\stdClass;

    public function begin_attempt(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context,
        string $handlerclass
    ): int;

    public function complete_attempt(
        int $attemptid,
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context,
        string $handlerclass,
        CommerceNativeFulfillmentResult $result
    ): void;
}
