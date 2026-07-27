<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\legacy\CommerceLegacyCatalogReader;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\service\CommerceCatalogReadService;
use local_subscriptions\commerce\catalog\service\CommerceHybridCatalogReadService;

final class commerce_catalog_migration_test extends advanced_testcase {
    public function test_dry_run_does_not_write_and_execute_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);

        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'A1 Full Test',
            'accessscopeid' => null,
            'duration_key' => 'lifetime',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $DB->insert_record('subscription_plan_price', (object)[
            'planid' => $planid,
            'currency' => 'EUR',
            'price' => '250.00',
            'stripe_price_id' => null,
        ]);

        $factory = new CommerceCatalogFactory($DB);
        $dryrun = $factory->importer()->import('subscription', false);
        $this->assertSame(1, $dryrun['processed']);
        $this->assertSame(0, $DB->count_records('local_subs_commerce_product'));

        $first = $factory->importer()->import('subscription', true);
        $second = $factory->importer()->import('subscription', true);
        $this->assertEmpty($first['errors']);
        $this->assertEmpty($second['errors']);
        $this->assertSame(1, $DB->count_records('local_subs_commerce_product'));
        $this->assertSame(1, $DB->count_records('local_subs_commerce_prod_price'));
        $this->assertSame(1, $DB->count_records('local_subs_commerce_prod_map'));
    }

    public function test_hybrid_reader_prefers_native_then_can_fall_back_to_legacy(): void {
        global $DB;
        $this->resetAfterTest(true);

        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Legacy Plan',
            'accessscopeid' => null,
            'duration_key' => '1month',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $translations = new CommerceProductTranslationRepository($DB, $hydrator, $products);
        $native = new CommerceCatalogReadService($products, $prices, $translations);
        $hybrid = new CommerceHybridCatalogReadService($native, new CommerceLegacyCatalogReader($DB));

        $sku = 'SUB.PLAN.' . $planid;
        $this->assertSame('Legacy Plan', $hybrid->find_by_sku($sku)?->get_name());

        $products->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'Native Plan'
        ));
        $this->assertSame('Native Plan', $hybrid->find_by_sku($sku)?->get_name());
    }

    public function test_parity_audit_is_equal_after_import(): void {
        global $DB;
        $this->resetAfterTest(true);

        $productid = (int)$DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'mobile-pdf-test',
            'name' => 'Mobile PDF Test',
            'description' => 'Test',
            'descriptionformat' => FORMAT_HTML,
            'filename' => 'test.pdf',
            'mobile_filename' => null,
            'price_eur' => '19.00',
            'price_rub' => '1900.00',
            'enabled' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'sortorder' => 0,
        ]);
        $this->assertGreaterThan(0, $productid);

        $factory = new CommerceCatalogFactory($DB);
        $result = $factory->importer()->import('digital', true);
        $this->assertEmpty($result['errors']);
        $audit = $factory->parity_auditor()->audit();
        $this->assertSame(1, $audit['checked']);
        $this->assertSame(1, $audit['equal']);
        $this->assertSame(0, $audit['different']);
        $this->assertSame(0, $audit['missing']);
    }
}
