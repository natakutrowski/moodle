<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

final class commerce_bundle_crm_backend_test extends advanced_testcase {
    public function test_manager_lists_unified_products_with_definition_counts(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product(
            'COURSE.ONE',
            CommerceProductType::COURSE_ACCESS
        ));
        $factory->product_manager()->save_bundle(
            $this->product('PACK.ONE', CommerceProductType::BUNDLE),
            [new CommerceProductComponent('PACK.ONE', 'COURSE.ONE')]
        );

        $summaries = $factory->product_manager()->list_products();
        $bytype = [];

        foreach ($summaries as $summary) {
            $bytype[$summary->get_product()->get_type()] = $summary;
        }

        $this->assertArrayHasKey(CommerceProductType::COURSE_ACCESS, $bytype);
        $this->assertArrayHasKey(CommerceProductType::BUNDLE, $bytype);
        $this->assertSame(1, $bytype[CommerceProductType::BUNDLE]->get_component_count());
    }

    public function test_manager_saves_and_previews_nested_bundle_atomically(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product(
            'DIGITAL.ONE',
            CommerceProductType::DIGITAL_DOWNLOAD
        ));
        $factory->product_manager()->save_bundle(
            $this->product('PACK.CHILD', CommerceProductType::BUNDLE),
            [new CommerceProductComponent('PACK.CHILD', 'DIGITAL.ONE', 2)]
        );
        $editor = $factory->product_manager()->save_bundle(
            $this->product('PACK.ROOT', CommerceProductType::BUNDLE),
            [new CommerceProductComponent('PACK.ROOT', 'PACK.CHILD', 3)]
        );

        $this->assertNotNull($editor->get_expansion());
        $this->assertSame(6, $editor->get_expansion()->get_total_quantity());
        $this->assertCount(1, $editor->get_components());
    }

    public function test_manager_rejects_duplicate_components_without_persisting_bundle(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product(
            'COURSE.ONE',
            CommerceProductType::COURSE_ACCESS
        ));

        try {
            $factory->product_manager()->save_bundle(
                $this->product('PACK.BAD', CommerceProductType::BUNDLE),
                [
                    new CommerceProductComponent('PACK.BAD', 'COURSE.ONE'),
                    new CommerceProductComponent('PACK.BAD', 'COURSE.ONE', 2),
                ]
            );
            $this->fail('Duplicate components should be rejected.');
        } catch (\coding_exception) {
            $this->assertNull($factory->bundle_read_service()->find('PACK.BAD'));
        }
    }

    public function test_manager_rolls_back_recursive_cycle(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product('PACK.A', CommerceProductType::BUNDLE));
        $factory->admin()->save_product($this->product('PACK.B', CommerceProductType::BUNDLE));
        $factory->admin()->replace_definition('PACK.B', [
            new CommerceProductComponent('PACK.B', 'PACK.A'),
        ], []);

        try {
            $factory->product_manager()->save_bundle(
                $this->product('PACK.A', CommerceProductType::BUNDLE),
                [new CommerceProductComponent('PACK.A', 'PACK.B')]
            );
            $this->fail('A recursive cycle should be rejected.');
        } catch (\coding_exception) {
            $this->assertSame([], $factory->bundle_read_service()->find('PACK.A')->get_components());
        }
    }

    public function test_crm_backend_audit_is_certified_without_bundles(): void {
        global $DB;

        $this->resetAfterTest();

        $report = (new CommerceCatalogFactory($DB))->bundle_crm_backend_auditor()->audit();

        $this->assertSame(0, $report['bundles']);
        $this->assertSame(0, $report['previewed']);
        $this->assertTrue($report['certified']);
    }

    private function product(string $sku, string $type): CommerceProduct {
        return new CommerceProduct(
            $sku,
            $type,
            CommerceProductStatus::ACTIVE,
            $sku
        );
    }
}
