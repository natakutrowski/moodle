<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Non-persistent Native executor contract used exclusively by Shadow. */
interface CommerceShadowNativeExecutor {
    public function execute(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult;
}
