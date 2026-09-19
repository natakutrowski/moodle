<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797l736_guest_ownership_storefront_ux_test extends \advanced_testcase {
    public function test_catalog_projects_guest_checkout_ownership_and_warns_on_repurchase(): void {
        $root = dirname(__DIR__, 3);
        $catalog = file_get_contents($root . '/digital_catalog.php');

        $this->assertIsString($catalog);
        $this->assertStringContainsString('CommerceStorefrontOwnershipResolver', $catalog);
        $this->assertStringContainsString("\$card['owned'] = true;", $catalog);
        $this->assertStringContainsString("\$card['canpurchase'] = false;", $catalog);
        $this->assertStringContainsString("'commerce_storefront_guest_owned_login'", $catalog);
        $this->assertStringContainsString("'commerce_cart_message_already_owned_guest'", $catalog);
        foreach ([
            'error',
            'bundle_all_owned',
            'already_owned',
            'promotion_join_not_eligible',
            'promotion_join_price_unavailable',
            'promotion_join_context_changed',
        ] as $warningcode) {
            $this->assertStringContainsString("'{$warningcode}'", $catalog);
        }
    }

    public function test_product_page_projects_same_guest_ownership_contract(): void {
        $root = dirname(__DIR__, 3);
        $product = file_get_contents($root . '/storefront_product.php');

        $this->assertIsString($product);
        $this->assertStringContainsString('CommerceStorefrontOwnershipResolver', $product);
        $this->assertStringContainsString("\$data['owned'] = true;", $product);
        $this->assertStringContainsString("\$data['canpurchase'] = false;", $product);
        $this->assertStringContainsString("'commerce_storefront_guest_owned_login'", $product);
        $this->assertStringContainsString("'commerce_cart_message_already_owned_guest'", $product);
    }
}
