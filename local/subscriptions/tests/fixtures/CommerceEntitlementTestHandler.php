<?php

declare(strict_types=1);

namespace local_subscriptions\tests\fixtures;

use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationContext;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationHandler;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

final class CommerceEntitlementTestHandler implements CommerceEntitlementApplicationHandler {
    public int $calls = 0;

    public function __construct(private readonly string $type = 'course_access') {
    }

    public function get_type(): string {
        return $this->type;
    }

    public function apply(
        CommerceEntitlementGrant $grant,
        CommerceEntitlementApplicationContext $context
    ): array {
        $this->calls++;

        return [
            'resourcekey' => $grant->get_resource_key(),
            'transactionid' => $context->get_transaction_id(),
        ];
    }
}
