<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Adapter that deliberately bypasses F5 persistence during Shadow dry-runs. */
final class CommerceKernelShadowNativeExecutor implements CommerceShadowNativeExecutor {
    public function __construct(private readonly CommerceNativeFulfillmentExecutor $executor) {
    }

    public function execute(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        if (!$context->is_dry_run()) {
            throw new \coding_exception('Commerce Shadow execution must always use a dry-run context.');
        }
        return $this->executor->execute($grant, $context);
    }
}
