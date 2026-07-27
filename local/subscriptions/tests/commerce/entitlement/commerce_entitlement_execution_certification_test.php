<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementExecutionCertificationAuditor;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementLedgerShadowAuditor;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementPlanningAuditor;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;

final class commerce_entitlement_execution_certification_test extends \advanced_testcase {
    public function test_empty_catalogue_is_certified_without_writes(): void {
        global $DB;

        $this->resetAfterTest();
        $factory = new CommerceCatalogFactory($DB);
        $planner = new CommerceEntitlementGrantPlanner();
        $repository = new CommerceEntitlementGrantRepository(
            $DB,
            new CommerceEntitlementGrantRecordMapper()
        );
        $auditor = new CommerceEntitlementExecutionCertificationAuditor(
            $DB,
            new CommerceEntitlementPlanningAuditor(
                $DB,
                $factory->purchase_preparation_service(),
                $planner
            ),
            new CommerceEntitlementLedgerShadowAuditor(
                $DB,
                $factory->purchase_preparation_service(),
                $planner,
                $repository
            )
        );

        $report = $auditor->audit();

        $this->assertTrue($report['certified']);
        $this->assertSame(0, $report['different']);
        $this->assertSame(0, $report['conflict']);
        $this->assertSame(0, $DB->count_records('local_subs_commerce_grant'));
    }
}
