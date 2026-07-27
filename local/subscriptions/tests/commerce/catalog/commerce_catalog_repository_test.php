<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_catalog_repository_test extends advanced_testcase {
    public function test_product_and_price_roundtrip(): void {
        global $DB;
        $this->resetAfterTest();

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);

        $saved = $products->save(new CommerceProduct(
            'A1.FULL',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 Full'
        ));
        $this->assertNotNull($saved->get_id());

        $products->save(new CommerceProduct(
            'A1.FULL',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 Full updated'
        ));
        $this->assertSame('A1 Full updated', $products->find_by_sku('a1.full')->get_name());

        $price = $prices->save(new CommerceProductPrice(
            'A1.FULL',
            CommerceMoney::from_minor(25000, 'EUR'),
            true,
            'stripe',
            'price_a1'
        ));
        $this->assertSame(25000, $price->get_amount_minor());
        $this->assertSame(
            'price_a1',
            $prices->find_active('A1.FULL', 'EUR', 'stripe')->get_provider_price_id()
        );
    }

    public function test_provider_neutral_price_is_used_as_provider_fallback(): void {
        global $DB;
        $this->resetAfterTest();

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);

        $products->save(new CommerceProduct(
            'DIGITAL.GUIDE-A1',
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ACTIVE,
            'Guide A1'
        ));
        $prices->save(new CommerceProductPrice(
            'DIGITAL.GUIDE-A1',
            CommerceMoney::from_minor(1990, 'EUR'),
            true
        ));

        $resolved = $prices->find_active('DIGITAL.GUIDE-A1', 'EUR', 'stripe');
        $this->assertNotNull($resolved);
        $this->assertSame(1990, $resolved->get_amount_minor());
        $this->assertNull($resolved->get_provider());
    }
}