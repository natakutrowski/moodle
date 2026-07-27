<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationContext;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationExecutor;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationHandlerRegistry;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\tests\fixtures\CommerceEntitlementTestHandler;

require_once(__DIR__ . '/../../fixtures/CommerceEntitlementTestHandler.php');

final class commerce_entitlement_application_test extends \advanced_testcase {
    public function test_registered_handler_applies_grant(): void {
        $handler = new CommerceEntitlementTestHandler();
        $executor = new CommerceEntitlementApplicationExecutor(
            new CommerceEntitlementApplicationHandlerRegistry([$handler])
        );

        $result = $executor->execute(
            $this->grant(),
            new CommerceEntitlementApplicationContext('txn-1', 'stripe', time())
        );

        $this->assertTrue($result->is_applied());
        $this->assertSame(1, $handler->calls);
        $this->assertSame('course:17:full', $result->get_payload()['resourcekey']);
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-1',
            'purchase-1',
            'item-1',
            'SUB.PLAN.30',
            'course_access',
            'course:17:full',
            1,
            42,
            'student@example.com',
            time()
        );
    }
}
