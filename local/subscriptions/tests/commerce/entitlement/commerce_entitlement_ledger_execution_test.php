<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationContext;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationExecutor;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationHandlerRegistry;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\execution\CommerceEntitlementLedgerExecutor;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\tests\fixtures\CommerceEntitlementTestHandler;

require_once(__DIR__ . '/../../fixtures/CommerceEntitlementTestHandler.php');

final class commerce_entitlement_ledger_execution_test extends \advanced_testcase {
    public function test_execution_is_idempotent_after_grant_is_applied(): void {
        global $DB;

        $this->resetAfterTest();
        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($DB, $mapper);
        $handler = new CommerceEntitlementTestHandler();
        $executor = new CommerceEntitlementLedgerExecutor(
            new CommerceEntitlementGrantPersister($DB, $repository),
            $repository,
            new CommerceEntitlementApplicationExecutor(
                new CommerceEntitlementApplicationHandlerRegistry([$handler])
            )
        );
        $now = time();
        $plan = new CommerceEntitlementGrantPlan('purchase-1', [$this->grant($now)], $now);
        $context = new CommerceEntitlementApplicationContext('txn-1', 'stripe', $now);

        $first = $executor->execute($plan, $context);
        $second = $executor->execute($plan, $context);

        $this->assertSame(1, $first->get_created());
        $this->assertSame(1, $first->get_applied());
        $this->assertSame(1, $second->get_identical());
        $this->assertSame(1, $second->get_skipped());
        $this->assertSame(1, $handler->calls);
    }

    private function grant(int $now): CommerceEntitlementGrant {
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
            $now
        );
    }
}
