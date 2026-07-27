<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\service\CommerceBundleExpander;

final class commerce_catalog_services_test extends advanced_testcase {
    public function test_nested_bundle_expansion_merges_quantities(): void {
        global $DB;

        $this->resetAfterTest();

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $components = new CommerceProductComponentRepository($DB, $hydrator, $products);

        $definitions = [
            ['PDF.ONE', CommerceProductType::DIGITAL_DOWNLOAD],
            ['PACK.INNER', CommerceProductType::BUNDLE],
            ['PACK.OUTER', CommerceProductType::BUNDLE],
        ];

        foreach ($definitions as [$sku, $type]) {
            $products->save(new CommerceProduct(
                $sku,
                $type,
                CommerceProductStatus::ACTIVE,
                $sku
            ));
        }

        $components->replace_for_parent('PACK.INNER', [
            new CommerceProductComponent('PACK.INNER', 'PDF.ONE', 2),
        ]);
        $components->replace_for_parent('PACK.OUTER', [
            new CommerceProductComponent('PACK.OUTER', 'PACK.INNER', 3),
            new CommerceProductComponent('PACK.OUTER', 'PDF.ONE', 1),
        ]);

        $items = (new CommerceBundleExpander($products, $components))->expand('PACK.OUTER', 2);

        $this->assertCount(1, $items);
        $this->assertSame('PDF.ONE', $items[0]->get_product()->get_sku());
        $this->assertSame(14, $items[0]->get_quantity());
    }
}
