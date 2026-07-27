<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\dto\ResolvedCommerceProduct;
use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchaseItemFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_catalog_purchase_item_test extends advanced_testcase {
    public function test_resolved_subscription_becomes_locked_purchase_item(): void {
        $resolved = new ResolvedCommerceProduct(
            new CommerceProduct('SUB.PLAN.21', CommerceProductType::COURSE_ACCESS, CommerceProductStatus::ACTIVE, 'A1', '', ['legacyfamily' => 'subscription', 'legacyid' => 21], 1),
            new CommerceProductPrice('SUB.PLAN.21', CommerceMoney::from_major('250.00', 'EUR')),
            null,
            [new CommerceProductEntitlementDefinition('SUB.PLAN.21', 'course_access', 'course:17:full')]
        );
        $item = (new CommerceCatalogPurchaseItemFactory())->create($resolved, 2);
        $this->assertSame('SUB.PLAN.21', $item->get_item()->get_reference());
        $this->assertSame(21, $item->get_item()->get_legacy_id());
        $this->assertSame(25000, $item->get_unit_amount_minor());
        $this->assertSame(50000, $item->get_total_amount_minor());
        $this->assertCount(1, $item->get_metadata_value('entitlements'));
    }
}
