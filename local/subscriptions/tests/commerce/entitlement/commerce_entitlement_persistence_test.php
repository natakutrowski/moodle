<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;

final class commerce_entitlement_persistence_test extends advanced_testcase {
    public function test_plan_is_persisted_idempotently(): void {
        global $DB;

        $this->resetAfterTest(true);

        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($DB, $mapper);
        $persister = new CommerceEntitlementGrantPersister($DB, $repository);
        $plan = new CommerceEntitlementGrantPlan(
            'purchase-1',
            [$this->grant()],
            1_700_000_000
        );

        $first = $persister->persist($plan, 1_700_000_100);
        $second = $persister->persist($plan, 1_700_000_200);

        $this->assertSame(1, $first->get_created());
        $this->assertSame(0, $first->get_identical());
        $this->assertSame(0, $second->get_created());
        $this->assertSame(1, $second->get_identical());
        $this->assertSame(1, $DB->count_records('local_subs_commerce_grant'));
    }

    public function test_same_idempotency_key_with_different_payload_is_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);

        $mapper = new CommerceEntitlementGrantRecordMapper();
        $repository = new CommerceEntitlementGrantRepository($DB, $mapper);
        $persister = new CommerceEntitlementGrantPersister($DB, $repository);
        $first = $this->grant();

        $persister->persist(new CommerceEntitlementGrantPlan(
            'purchase-1',
            [$first],
            1_700_000_000
        ));

        $conflicting = new CommerceEntitlementGrant(
            $first->get_reference(),
            $first->get_purchase_reference(),
            $first->get_item_reference(),
            $first->get_product_sku(),
            $first->get_type(),
            $first->get_resource_key(),
            2,
            $first->get_beneficiary_user_id(),
            $first->get_beneficiary_email(),
            $first->get_valid_from(),
            $first->get_valid_until(),
            $first->get_configuration(),
            $first->get_metadata()
        );

        $this->expectException(\RuntimeException::class);
        $persister->persist(new CommerceEntitlementGrantPlan(
            'purchase-1',
            [$conflicting],
            1_700_000_000
        ));
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-1',
            'purchase-1',
            'SUB.PLAN.30',
            'SUB.PLAN.30',
            'course_access',
            'course:17:full',
            1,
            42,
            'student@example.com',
            1_700_000_000,
            null,
            ['roleshortname' => 'student'],
            ['definitionid' => 7]
        );
    }
}
