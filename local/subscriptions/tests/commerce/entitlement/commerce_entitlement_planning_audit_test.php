<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementPlanningAuditor;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;

final class commerce_entitlement_planning_audit_test extends advanced_testcase {
    public function test_imported_active_product_has_equal_definition_and_plan_counts(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Entitlement audit plan',
            'accessscopeid' => null,
            'duration_key' => 'lifetime',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);

        $DB->insert_record('subscription_plan_price', (object)[
            'planid' => $planid,
            'currency' => 'EUR',
            'price' => 20,
            'stripe_price_id' => null,
        ]);

        $DB->insert_record('subscription_plan_entitlement', (object)[
            'planid' => $planid,
            'courseid' => (int)$course->id,
            'accesslevel' => 'full',
            'roleshortname' => 'student',
            'groupname' => '',
            'priority' => 0,
            'timecreated' => $now,
            'lastupdate' => $now,
        ]);

        $factory = new CommerceCatalogFactory($DB);
        $factory->importer()->import('subscription', true);

        $report = (new CommerceEntitlementPlanningAuditor(
            $DB,
            $factory->purchase_preparation_service(),
            new CommerceEntitlementGrantPlanner()
        ))->audit('fr');

        $this->assertSame(0, $report['different']);
        $this->assertSame([], $report['errors']);
        $this->assertSame($report['definitions'], $report['planned']);
    }
}
