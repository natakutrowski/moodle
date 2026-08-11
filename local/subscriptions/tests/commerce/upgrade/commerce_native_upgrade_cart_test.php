<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontUpgrade;

final class commerce_native_upgrade_cart_test extends advanced_testcase {
    public function test_upgrade_contract_is_preserved_in_cart_item(): void {
        $item = new CommerceCartItem('sub.plan.32', 22, 1, [
            'operation' => 'upgrade',
            'sourceplanid' => 31,
            'targetplanid' => 32,
            'upgradeamountminor' => 7000,
        ]);

        $restored = CommerceCartItem::from_array($item->to_array());
        $this->assertSame('SUB.PLAN.32', $restored->get_product_sku());
        $this->assertSame('upgrade', $restored->get_metadata()['operation']);
        $this->assertSame(31, $restored->get_metadata()['sourceplanid']);
        $this->assertSame(32, $restored->get_metadata()['targetplanid']);
        $this->assertSame(7000, $restored->get_metadata()['upgradeamountminor']);
    }

    public function test_storefront_upgrade_exposes_plan_contract(): void {
        $upgrade = new CommerceStorefrontUpgrade(
            7000,
            'EUR',
            'A2 Grammar',
            'A2 Full',
            'Pay only the difference.',
            31,
            32
        );

        $this->assertSame(31, $upgrade->get_from_plan_id());
        $this->assertSame(32, $upgrade->get_to_plan_id());
        $this->assertSame(7000, $upgrade->get_amount_minor());
    }

    public function test_storefront_upgrade_forms_use_native_cart_action(): void {
        global $CFG;

        foreach (['product_card.mustache', 'product_commerce_panel.mustache'] as $template) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/templates/storefront/' . $template
            );
            $this->assertIsString($source);
            $this->assertStringContainsString('name="operation" value="upgrade"', $source);
            $this->assertStringContainsString('name="targetplanid"', $source);
            $this->assertStringContainsString('name="priceid" value="{{upgradepriceid}}"', $source);
            $this->assertStringNotContainsString('upgradeactionurl', $source);
        }
    }

    public function test_fulfillment_keeps_upgrade_metadata(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePurchaseGrantPlanner.php'
        );
        $this->assertIsString($source);
        $this->assertStringContainsString("'commerceoperation' => 'upgrade'", $source);
        $this->assertStringContainsString("'sourceplanid'", $source);
        $this->assertStringContainsString("'targetplanid'", $source);
    }
}
