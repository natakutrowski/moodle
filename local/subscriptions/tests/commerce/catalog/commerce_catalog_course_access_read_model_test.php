<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\status\CommerceCatalogTechnicalState;

/**
 * @covers \local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository
 * @covers \local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator
 */
final class commerce_catalog_course_access_read_model_test extends advanced_testcase {
    public function test_course_product_with_price_and_access_scope_is_technically_valid_without_entitlement_rows(): void {
        global $DB;

        $this->resetAfterTest();
        $now = time();

        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'A2 access',
            'course_ids' => json_encode([14]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'COURSE.ACCESS.A2.TEST',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'A2 Test',
            'description' => '',
            'metadatajson' => json_encode([
                'access' => [
                    'scopeid' => $scopeid,
                ],
            ]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_prod_price', (object)[
            'productid' => $productid,
            'currency' => 'EUR',
            'amountminor' => 10000,
            'provider' => null,
            'providerpriceid' => null,
            'active' => 1,
            'metadatajson' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $this->assertFalse($DB->record_exists('local_subs_commerce_prod_ent', ['productid' => $productid]));

        $details = (new CommerceCatalogReadRepository($DB))->find_by_sku('COURSE.ACCESS.A2.TEST');

        $this->assertNotNull($details);
        $this->assertSame(
            CommerceCatalogTechnicalState::VALID,
            $details->get_summary()->get_technical_state()
        );
    }
}
