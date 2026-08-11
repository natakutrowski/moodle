<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\audit\CommerceCatalogLegacyInventoryAuditor;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\status\CommerceCatalogAvailability;
use local_subscriptions\commerce\catalog\status\CommerceCatalogEditorialStatus;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusResolver;
use local_subscriptions\commerce\catalog\status\CommerceCatalogTechnicalState;
use local_subscriptions\commerce\catalog\status\CommerceCatalogVisibility;

/**
 * Tests the 7.95E1-E4 unified catalogue foundation.
 *
 * @covers \local_subscriptions\commerce\catalog\audit\CommerceCatalogLegacyInventoryAuditor
 * @covers \local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository
 * @covers \local_subscriptions\commerce\catalog\status\CommerceCatalogStatusResolver
 */
final class commerce_catalog_e1_e4_foundation_test extends advanced_testcase {

    public function test_status_resolver_separates_four_dimensions(): void {
        $resolver = new CommerceCatalogStatusResolver();
        $status = $resolver->resolve('active', 100, 300, ['visibility' => 'direct_link'], 200, true);

        $this->assertSame(CommerceCatalogEditorialStatus::PUBLISHED, $status->get_editorial());
        $this->assertSame(CommerceCatalogVisibility::DIRECT_LINK, $status->get_visibility());
        $this->assertSame(CommerceCatalogAvailability::ON_SALE, $status->get_availability());
        $this->assertSame(CommerceCatalogTechnicalState::VALID, $status->get_technical());
    }

    public function test_status_resolver_marks_upcoming_ended_and_incomplete_products(): void {
        $resolver = new CommerceCatalogStatusResolver();

        $upcoming = $resolver->resolve('active', 300, null, [], 200, false);
        $ended = $resolver->resolve('active', null, 100, [], 200, true);

        $this->assertSame(CommerceCatalogAvailability::UPCOMING, $upcoming->get_availability());
        $this->assertSame(CommerceCatalogTechnicalState::INCOMPLETE, $upcoming->get_technical());
        $this->assertSame(CommerceCatalogAvailability::ENDED, $ended->get_availability());
    }

    public function test_repository_federates_native_and_unmapped_legacy_products(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $nativeid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'NATIVE.A1',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'Native A1',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_price', (object)[
            'productid' => $nativeid,
            'currency' => 'EUR',
            'amountminor' => 25000,
            'provider' => 'stripe',
            'providerpriceid' => null,
            'active' => 1,
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $nativeid,
            'type' => 'course_enrolment',
            'resourcekey' => 'course:17',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => '{}',
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Legacy A2',
            'accessscopeid' => null,
            'duration_key' => 'lifetime',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'highlight_type' => null,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_days' => null,
            'expiry_reminder_enabled' => 1,
        ]);
        $DB->insert_record('subscription_plan_price', (object)[
            'planid' => $planid,
            'currency' => 'EUR',
            'price' => 300.00,
            'stripe_price_id' => null,
        ]);

        $products = (new CommerceCatalogReadRepository($DB))->find_all();
        $origins = array_map(static fn($product): string => $product->get_origin(), $products);

        $this->assertContains('native', $origins);
        $this->assertContains('legacy_plan', $origins);
    }

    public function test_inventory_auditor_is_read_only_and_reports_catalogue_tables(): void {
        global $DB;
        $this->resetAfterTest(true);

        $report = (new CommerceCatalogLegacyInventoryAuditor($DB))->audit();

        $this->assertArrayHasKey('subscription_plan', $report->get_counts());
        $this->assertArrayHasKey('local_subs_commerce_product', $report->get_counts());
        $this->assertTrue($report->is_healthy());
    }
}
