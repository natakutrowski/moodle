<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;

final class commerce_entitlement_domain_test extends advanced_testcase {
    public function test_lifetime_grant_exposes_stable_identity(): void {
        $grant = new CommerceEntitlementGrant(
            'grant-1',
            'purchase-1',
            'SUB.PLAN.30',
            'SUB.PLAN.30',
            'course_access',
            'course:17:full',
            1,
            42,
            'student@example.com',
            1_700_000_000
        );

        $this->assertTrue($grant->is_lifetime());
        $this->assertSame(42, $grant->get_beneficiary_user_id());
        $this->assertSame('SUB.PLAN.30', $grant->get_product_sku());
        $this->assertNotSame('', $grant->get_idempotency_key());
    }

    public function test_plan_groups_grants_by_type(): void {
        $grant = new CommerceEntitlementGrant(
            'grant-1',
            'purchase-1',
            'DIGITAL.VERBES-3E-GROUPE',
            'DIGITAL.VERBES-3E-GROUPE',
            'digital_download',
            'digital:2',
            1,
            null,
            'guest@example.com',
            1_700_000_000,
            1_700_086_400
        );

        $plan = new CommerceEntitlementGrantPlan(
            'purchase-1',
            [$grant],
            1_700_000_001
        );

        $this->assertSame(1, $plan->count());
        $this->assertCount(1, $plan->find_by_type('digital_download'));
        $this->assertFalse($plan->is_empty());
    }
}
