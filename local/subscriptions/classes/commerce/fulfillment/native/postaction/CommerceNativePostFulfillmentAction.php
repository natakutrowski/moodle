<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Optional secondary action executed only after a successful Native grant. */
interface CommerceNativePostFulfillmentAction {
    public function get_key(): string;

    public function supports(CommerceNativeFulfillmentResult $result): bool;

    public function execute(
        CommerceNativeFulfillmentResult $result,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativePostFulfillmentActionResult;
}
