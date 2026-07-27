<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

final class commerce_catalog_purchase_shadow_test extends advanced_testcase {
    public function test_imported_legacy_plan_is_equal_in_purchase_shadow(): void {
        $this->resetAfterTest(true);
        global $DB;
        $scopeid = $DB->insert_record('subscription_access_scope', (object)['name' => 'A1', 'course_ids' => '17', 'creation_date' => 0, 'last_update' => 0]);
        $planid = $DB->insert_record('subscription_plan', (object)['name' => 'A1 Full', 'accessscopeid' => $scopeid, 'duration_key' => 'lifetime', 'is_active' => 1, 'creation_date' => 0, 'last_update' => 0, 'is_recurring' => 0, 'is_trial' => 0, 'expiry_reminder_enabled' => 1]);
        $DB->insert_record('subscription_plan_price', (object)['planid' => $planid, 'currency' => 'EUR', 'price' => '250.00']);
        $factory = new CommerceCatalogFactory($DB);
        $import = $factory->importer()->import('subscription', true);
        $this->assertEmpty($import['errors']);
        $report = $factory->purchase_shadow_auditor()->audit('fr');
        $this->assertSame(1, $report['checked']);
        $this->assertSame(1, $report['equal']);
        $this->assertSame(0, $report['different']);
    }
}
