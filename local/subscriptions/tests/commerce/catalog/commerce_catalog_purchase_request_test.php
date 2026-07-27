<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\purchase\CommerceCustomer;

final class commerce_catalog_purchase_request_test extends advanced_testcase {
    public function test_factory_expands_bundle_and_aggregates_quantities(): void {
        $this->resetAfterTest(true);
        global $DB;
        $factory = new CommerceCatalogFactory($DB);
        $admin = $factory->admin();
        $admin->save_product(new CommerceProduct('DIGITAL.A', CommerceProductType::DIGITAL_DOWNLOAD, CommerceProductStatus::ACTIVE, 'A', '', ['legacyfamily' => 'digital', 'legacyid' => 7]));
        $admin->save_product(new CommerceProduct('DIGITAL.B', CommerceProductType::DIGITAL_DOWNLOAD, CommerceProductStatus::ACTIVE, 'B', '', ['legacyfamily' => 'digital', 'legacyid' => 8]));
        $admin->save_product(new CommerceProduct('BUNDLE.TEST', CommerceProductType::BUNDLE, CommerceProductStatus::ACTIVE, 'Bundle'));
        $admin->set_price(new CommerceProductPrice('DIGITAL.A', CommerceMoney::from_major('10.00', 'EUR')));
        $admin->set_price(new CommerceProductPrice('DIGITAL.B', CommerceMoney::from_major('5.00', 'EUR')));
        $admin->replace_definition('BUNDLE.TEST', [
            new CommerceProductComponent('BUNDLE.TEST', 'DIGITAL.A', 1),
            new CommerceProductComponent('BUNDLE.TEST', 'DIGITAL.B', 2),
        ], []);

        $request = $factory->purchase_request_factory()->create(
            'catalogue-test',
            new CommerceCustomer(null, 'buyer@example.test'),
            [['sku' => 'BUNDLE.TEST', 'quantity' => 2]],
            'EUR',
            'fr'
        );
        $this->assertCount(2, $request->get_items());
        $this->assertSame(4000, $request->get_total_amount_minor());
        $this->assertSame('native', $request->get_metadata_value('catalogue_source'));
    }
}
