<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

final class commerce_bundle_expansion_test extends advanced_testcase {
    public function test_leaf_product_expands_to_itself(): void {
        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product('COURSE.ONE', CommerceProductType::COURSE_ACCESS));

        $result = (new CommerceBundleExpansionService($products, $components))->expand(
            'COURSE.ONE',
            2
        );

        $this->assertSame(1, $result->get_item_count());
        $this->assertSame(2, $result->get_total_quantity());
        $this->assertSame('COURSE.ONE', $result->get_items()[0]->get_sku());
    }

    public function test_nested_bundle_expansion_aggregates_quantities(): void {
        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product('COURSE.ONE', CommerceProductType::COURSE_ACCESS));
        $products->save($this->product('DIGITAL.ONE', CommerceProductType::DIGITAL_DOWNLOAD));
        $products->save($this->product('PACK.CHILD', CommerceProductType::BUNDLE));
        $products->save($this->product('PACK.ROOT', CommerceProductType::BUNDLE));

        $components->replace_for_parent('PACK.CHILD', [
            new CommerceProductComponent('PACK.CHILD', 'COURSE.ONE', 2),
        ]);
        $components->replace_for_parent('PACK.ROOT', [
            new CommerceProductComponent('PACK.ROOT', 'PACK.CHILD', 3),
            new CommerceProductComponent('PACK.ROOT', 'COURSE.ONE', 1),
            new CommerceProductComponent('PACK.ROOT', 'DIGITAL.ONE', 2),
        ]);

        $result = (new CommerceBundleExpansionService($products, $components))->expand('PACK.ROOT');
        $items = [];

        foreach ($result->get_items() as $item) {
            $items[$item->get_sku()] = $item->get_quantity();
        }

        $this->assertSame(7, $items['COURSE.ONE']);
        $this->assertSame(2, $items['DIGITAL.ONE']);
        $this->assertSame(2, $result->get_bundles_visited());
        $this->assertSame(2, $result->get_maximum_depth());
    }

    public function test_expansion_rejects_recursive_cycle(): void {
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

        $this->expectException(\coding_exception::class);
        (new CommerceBundleExpansionService($products, $components))->expand('PACK.A');
    }

    public function test_expansion_rejects_inactive_leaf(): void {
        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product(
            'DIGITAL.OLD',
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ARCHIVED
        ));
        $products->save($this->product('PACK.A', CommerceProductType::BUNDLE));
        $components->replace_for_parent('PACK.A', [
            new CommerceProductComponent('PACK.A', 'DIGITAL.OLD'),
        ]);

        $this->expectException(\coding_exception::class);
        (new CommerceBundleExpansionService($products, $components))->expand('PACK.A');
    }


    public function test_draft_root_bundle_can_be_expanded_for_admin_validation(): void {
        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product('COURSE.ONE', CommerceProductType::COURSE_ACCESS));
        $products->save($this->product(
            'PACK.DRAFT',
            CommerceProductType::BUNDLE,
            CommerceProductStatus::DRAFT
        ));
        $components->replace_for_parent('PACK.DRAFT', [
            new CommerceProductComponent('PACK.DRAFT', 'COURSE.ONE'),
        ]);

        $result = (new CommerceBundleExpansionService($products, $components))->expand(
            'PACK.DRAFT',
            1,
            true
        );

        $this->assertSame(1, $result->get_item_count());
        $this->assertSame('COURSE.ONE', $result->get_items()[0]->get_sku());
    }

    public function test_admin_validation_still_rejects_inactive_descendant(): void {
        $this->resetAfterTest();

        [$products, $components] = $this->repositories();
        $products->save($this->product(
            'COURSE.INACTIVE',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::INACTIVE
        ));
        $products->save($this->product(
            'PACK.DRAFT',
            CommerceProductType::BUNDLE,
            CommerceProductStatus::DRAFT
        ));
        $components->replace_for_parent('PACK.DRAFT', [
            new CommerceProductComponent('PACK.DRAFT', 'COURSE.INACTIVE'),
        ]);

        $this->expectException(\coding_exception::class);
        (new CommerceBundleExpansionService($products, $components))->expand(
            'PACK.DRAFT',
            1,
            true
        );
    }

    public function test_expansion_audit_is_certified_without_bundles(): void {
        global $DB;

        $this->resetAfterTest();

        $report = (new CommerceCatalogFactory($DB))->bundle_expansion_auditor()->audit();

        $this->assertSame(0, $report['checked']);
        $this->assertSame(0, $report['expanded']);
        $this->assertTrue($report['certified']);
    }

    private function product(
        string $sku,
        string $type,
        string $status = CommerceProductStatus::ACTIVE
    ): CommerceProduct {
        return new CommerceProduct($sku, $type, $status, $sku);
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
