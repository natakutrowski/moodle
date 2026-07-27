<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\bundle\domain\CommerceBundle;
use local_subscriptions\commerce\bundle\domain\CommerceBundleCollection;
use local_subscriptions\commerce\bundle\repository\CommerceBundleRepository;
use local_subscriptions\commerce\bundle\service\CommerceBundleDomainValidator;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

final class commerce_bundle_domain_test extends advanced_testcase {

    public function test_default_product_type_registry_exposes_bundle_capabilities(): void {
        $registry = CommerceProductTypeRegistry::create_default();
        $bundle = $registry->get(CommerceProductType::BUNDLE);

        $this->assertCount(4, $registry->all());
        $this->assertTrue($bundle->get_capabilities()->is_composable());
        $this->assertTrue($bundle->get_capabilities()->is_expandable());
        $this->assertFalse($bundle->get_capabilities()->supports_entitlements());
    }

    public function test_registry_rejects_duplicate_product_type_codes(): void {
        $registry = CommerceProductTypeRegistry::create_default();

        $this->expectException(\coding_exception::class);
        $registry->register($registry->get(CommerceProductType::BUNDLE));
    }

    public function test_bundle_aggregate_orders_components(): void {
        $bundle = new CommerceBundle(
            $this->product('PACK.A1', CommerceProductType::BUNDLE),
            [
                new CommerceProductComponent('PACK.A1', 'DIGITAL.ONE', 1, 20),
                new CommerceProductComponent('PACK.A1', 'COURSE.ONE', 1, 10),
            ]
        );

        $components = $bundle->get_components();

        $this->assertSame('COURSE.ONE', $components[0]->get_child_product_sku());
        $this->assertSame('DIGITAL.ONE', $components[1]->get_child_product_sku());
    }

    public function test_bundle_collection_is_keyed_by_normalised_sku(): void {
        $bundle = new CommerceBundle(
            $this->product('PACK.A1', CommerceProductType::BUNDLE),
            []
        );
        $collection = new CommerceBundleCollection([$bundle]);

        $this->assertTrue($collection->has('pack.a1'));
        $this->assertSame($bundle, $collection->get('PACK.A1'));
    }

    public function test_repository_builds_bundle_from_generic_catalogue_tables(): void {
        global $DB;

        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product('COURSE.ONE', CommerceProductType::COURSE_ACCESS));
        $products->save($this->product('PACK.A1', CommerceProductType::BUNDLE));
        $components->replace_for_parent('PACK.A1', [
            new CommerceProductComponent('PACK.A1', 'COURSE.ONE'),
        ]);

        $repository = new CommerceBundleRepository($products, $components);
        $bundle = $repository->find_by_sku('PACK.A1');

        $this->assertNotNull($bundle);
        $this->assertSame(1, $bundle->get_component_count());
        $this->assertSame('COURSE.ONE', $bundle->get_components()[0]->get_child_product_sku());
    }

    public function test_validator_detects_inactive_component(): void {
        global $DB;

        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product(
            'COURSE.ARCHIVED',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ARCHIVED
        ));
        $products->save($this->product('PACK.A1', CommerceProductType::BUNDLE));
        $components->replace_for_parent('PACK.A1', [
            new CommerceProductComponent('PACK.A1', 'COURSE.ARCHIVED'),
        ]);

        $bundles = (new CommerceBundleRepository($products, $components))->find_all();
        $report = (new CommerceBundleDomainValidator($products))->validate($bundles);

        $this->assertSame(1, $report['disabled']);
        $this->assertNotEmpty($report['errors']);
    }

    public function test_validator_detects_recursive_bundle_cycle(): void {
        global $DB;

        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product('PACK.A', CommerceProductType::BUNDLE));
        $products->save($this->product('PACK.B', CommerceProductType::BUNDLE));
        $components->replace_for_parent('PACK.A', [
            new CommerceProductComponent('PACK.A', 'PACK.B'),
        ]);
        $components->replace_for_parent('PACK.B', [
            new CommerceProductComponent('PACK.B', 'PACK.A'),
        ]);

        $bundles = (new CommerceBundleRepository($products, $components))->find_all();
        $report = (new CommerceBundleDomainValidator($products))->validate($bundles);

        $this->assertSame(2, $report['cycles']);
        $this->assertCount(2, $report['errors']);
    }

    public function test_domain_audit_is_certified_when_no_bundle_exists(): void {
        global $DB;

        $this->resetAfterTest();

        $report = (new CommerceCatalogFactory($DB))->bundle_domain_auditor()->audit();

        $this->assertSame(0, $report['bundles']);
        $this->assertTrue($report['bundletyperegistered']);
        $this->assertTrue($report['certified']);
    }

    private function product(
        string $sku,
        string $type,
        string $status = CommerceProductStatus::ACTIVE
    ): CommerceProduct {
        return new CommerceProduct(
            $sku,
            $type,
            $status,
            $sku
        );
    }

    private function repositories(): array {
        global $DB;

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $components = new CommerceProductComponentRepository(
            $DB,
            $hydrator,
            $products
        );

        return [$products, $components];
    }
}
