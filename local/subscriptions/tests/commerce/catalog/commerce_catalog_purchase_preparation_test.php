<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

final class commerce_catalog_purchase_preparation_test extends advanced_testcase {
    public function test_imported_subscription_can_be_prepared_from_native_sku(): void {
        $this->resetAfterTest(true);
        global $DB;

        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A1',
            'course_ids' => '17',
            'creation_date' => 0,
            'last_update' => 0,
        ]);
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'A1 Full',
            'accessscopeid' => $scopeid,
            'duration_key' => 'lifetime',
            'is_active' => 1,
            'creation_date' => 0,
            'last_update' => 0,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $DB->insert_record('subscription_plan_price', (object)[
            'planid' => $planid,
            'currency' => 'EUR',
            'price' => '250.00',
        ]);

        $factory = new CommerceCatalogFactory($DB);
        $result = $factory->importer()->import('subscription', true);
        $this->assertEmpty($result['errors']);

        $preparation = $factory->purchase_preparation_service()->prepare(
            'c4-test',
            new CommerceCustomer(2, 'buyer@example.test'),
            [['sku' => 'SUB.PLAN.' . $planid]],
            'EUR',
            'fr'
        );

        $this->assertSame(25000, $preparation->get_total_amount_minor());
        $this->assertCount(1, $preparation->get_items());
        $this->assertSame('subscription', $preparation->get_items()[0]->get_handler_key());
        $this->assertSame($planid, $preparation->get_fulfillment_operations()[0]['metadata']['planid']);
    }
}
