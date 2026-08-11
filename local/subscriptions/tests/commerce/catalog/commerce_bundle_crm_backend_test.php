<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingConfiguration;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\status\CommerceCatalogTechnicalState;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;

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


    public function test_admin_preview_and_pricing_accept_inactive_root_bundle(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product(
            'COURSE.ONE',
            CommerceProductType::COURSE_ACCESS
        ));
        $factory->admin()->set_price(new \local_subscriptions\commerce\catalog\domain\CommerceProductPrice(
            'COURSE.ONE',
            \local_subscriptions\commerce\domain\value\CommerceMoney::from_major('100.00', 'EUR')
        ));

        $draft = new CommerceProduct(
            'PACK.DRAFT',
            CommerceProductType::BUNDLE,
            CommerceProductStatus::DRAFT,
            'Draft bundle'
        );
        $factory->product_manager()->save_bundle(
            $draft,
            [new CommerceProductComponent('PACK.DRAFT', 'COURSE.ONE')]
        );

        $factory->bundle_pricing_service()->configure(
            'PACK.DRAFT',
            new CommerceBundlePricingConfiguration(CommerceBundlePricingStrategy::COMPONENT_SUM)
        );

        $preview = $factory->bundle_preview_service()->build('PACK.DRAFT', true);
        $quote = $factory->bundle_pricing_service()->quote('PACK.DRAFT', 'EUR', true);
        $persistedprice = $DB->get_record('local_subs_commerce_prod_price', [
            'productid' => $factory->product_manager()->get_editor_data('PACK.DRAFT')->get_product()->get_id(),
            'currency' => 'EUR',
            'active' => 1,
        ]);

        $this->assertSame(1, $preview->get_product_count());
        $this->assertSame(10000, $quote->get_final_price()->get_amount_minor());
        $this->assertNotNull($persistedprice);
        $this->assertSame(10000, (int)$persistedprice->amountminor);
    }

    public function test_strict_preview_still_rejects_inactive_root_bundle(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        $factory->admin()->save_product($this->product(
            'COURSE.ONE',
            CommerceProductType::COURSE_ACCESS
        ));
        $factory->product_manager()->save_bundle(
            new CommerceProduct(
                'PACK.DRAFT',
                CommerceProductType::BUNDLE,
                CommerceProductStatus::DRAFT,
                'Draft bundle'
            ),
            [new CommerceProductComponent('PACK.DRAFT', 'COURSE.ONE')]
        );

        $this->expectException(\coding_exception::class);
        $factory->bundle_preview_service()->build('PACK.DRAFT');
    }

    public function test_calculated_bundle_uses_only_common_component_currencies_and_is_valid(): void {
        global $DB;

        $this->resetAfterTest();

        $factory = new CommerceCatalogFactory($DB);
        foreach (['COURSE.ONE', 'COURSE.TWO'] as $sku) {
            $factory->admin()->save_product($this->product($sku, CommerceProductType::COURSE_ACCESS));
            $factory->admin()->set_price(new \local_subscriptions\commerce\catalog\domain\CommerceProductPrice(
                $sku,
                \local_subscriptions\commerce\domain\value\CommerceMoney::from_major('100.00', 'EUR')
            ));
        }
        $factory->admin()->set_price(new \local_subscriptions\commerce\catalog\domain\CommerceProductPrice(
            'COURSE.ONE',
            \local_subscriptions\commerce\domain\value\CommerceMoney::from_major('9000.00', 'RUB')
        ));

        $bundle = new CommerceProduct(
            'PACK.COMMON.CURRENCY',
            CommerceProductType::BUNDLE,
            CommerceProductStatus::ACTIVE,
            'Common currency bundle',
            '',
            (new CommerceBundlePricingConfiguration(
                CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT,
                1000
            ))->apply_to_metadata([])
        );
        $factory->product_manager()->save_bundle($bundle, [
            new CommerceProductComponent('PACK.COMMON.CURRENCY', 'COURSE.ONE'),
            new CommerceProductComponent('PACK.COMMON.CURRENCY', 'COURSE.TWO'),
        ]);
        $factory->bundle_pricing_service()->configure(
            'PACK.COMMON.CURRENCY',
            new CommerceBundlePricingConfiguration(
                CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT,
                1000
            )
        );

        $this->assertSame(
            ['EUR'],
            $factory->currency_service()->get_product_currencies('PACK.COMMON.CURRENCY', true, true)
        );

        $saved = $factory->product_manager()->get_editor_data('PACK.COMMON.CURRENCY')->get_product();
        $this->assertNotNull($saved);
        $this->assertTrue((new CommerceCatalogActivationValidator($DB))->validate($saved)->is_valid());

        $summary = null;
        foreach ((new CommerceCatalogReadRepository($DB))->find_all() as $candidate) {
            if ($candidate->get_sku() === 'PACK.COMMON.CURRENCY') {
                $summary = $candidate;
                break;
            }
        }
        $this->assertNotNull($summary);
        $this->assertSame(CommerceCatalogTechnicalState::VALID, $summary->get_technical_state());
        $this->assertCount(1, array_filter(
            $summary->get_prices(),
            static fn($price): bool => $price->is_active() && $price->get_currency() === 'EUR'
        ));
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
